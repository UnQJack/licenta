<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking_id'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: bookings.php");
        exit;
    }

    $deleteBookingId = (int)$_POST['delete_booking_id'];

    $deleteStmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $deleteStmt->bind_param("i", $deleteBookingId);
    $deleteStmt->execute();
    $deleteStmt->close();

    header("Location: bookings.php");
    exit;
}

$bookingStatsSql = "
    SELECT
        COUNT(*) AS total_bookings,
        SUM(status = 'Paid') AS paid_bookings,
        SUM(status = 'Pending') AS pending_bookings,
        SUM(status = 'Cancelled') AS cancelled_bookings
    FROM bookings
";
$bookingStatsStmt = $conn->query($bookingStatsSql);
$bookingStats = $bookingStatsStmt->fetch_assoc();

$bookingsSql = "
    SELECT
        b.id,
        u.name AS user_name,
        u.email,
        f.flight_number,
        a.name AS airline_name,
        ao.iata_code AS origin_code,
        ad.iata_code AS destination_code,
        b.price,
        b.status,
        b.created_at
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN flights f ON b.flight_id = f.id
    JOIN airlines a ON f.airline_id = a.id
    JOIN airports ao ON f.origin_airport_id = ao.id
    JOIN airports ad ON f.destination_airport_id = ad.id
    ORDER BY b.created_at ASC
";
$bookingsStmt = $conn->query($bookingsSql);
$bookings = [];

while ($row = $bookingsStmt->fetch_assoc()) {
    $bookings[] = $row;
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skytix - Rezervări</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3efe4;
            color: #1f1f1f;
        }

        .page-wrapper {
            max-width: 1450px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .dashboard-shell {
            background: #efeded;
            border-radius: 30px;
            display: grid;
            grid-template-columns: 240px 1fr;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
        }

        .sidebar {
            background: #f7f7f7;
            padding: 28px 20px;
            min-height: 850px;
        }

        .sidebar-logo {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 12px;
        }

        .menu li a {
            display: block;
            text-decoration: none;
            color: #5a5a5a;
            font-weight: 500;
            padding: 14px 16px;
            border-radius: 14px;
        }

        .menu li a:hover {
            background: #e8e8e8;
        }

        .menu li.active a {
            background: #e4d09c;
            color: #1f1f1f;
            font-weight: 700;
        }

        .main {
            padding: 26px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .topbar h2 {
            font-size: 24px;
        }

        .search {
            background: white;
            border: none;
            border-radius: 14px;
            padding: 12px 16px;
            width: 280px;
            outline: none;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        }

        .card small {
            color: #8a8a8a;
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .card .value {
            font-size: 40px;
            font-weight: 700;
        }

        .table-card {
            background: white;
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        }

        .table-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bookings-table th,
        .bookings-table td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #ececec;
            font-size: 15px;
        }

        .bookings-table th {
            color: #8a8a8a;
            font-weight: 600;
            background: #fafafa;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .status-paid {
            background: #e4d09c;
            color: #1f1f1f;
        }

        .status-pending {
            background: #f5edd1;
            color: #7a5a1a;
        }

        .status-cancelled {
            background: #1f1f1f;
            color: #ffffff;
        }

        .price {
            font-weight: 700;
        }

        .route {
            font-weight: 600;
        }

        .empty-text {
            color: #8b8b8b;
            font-size: 15px;
            margin-top: 10px;
        }

        @media (max-width: 1200px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .bookings-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 850px) {
            .dashboard-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                min-height: auto;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            .search {
                width: 100%;
            }
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #d8b75b;
            color: #1f1f1f;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            transition: 0.2s ease;
        }

        .add-btn:hover {
            background: #cfae4f;
        }

        .delete-btn {
            border: none;
            background: #1f1f1f;
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .delete-btn:hover {
            opacity: 0.85;
        }

        .filters-bar {
    display: flex;
    gap: 14px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filters-bar input,
.filters-bar select {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 15px;
    outline: none;
}

.filters-bar input {
    min-width: 320px;
    flex: 1;
}

.filters-bar select {
    min-width: 180px;
}
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="dashboard-shell">
            <aside class="sidebar">
                <div class="sidebar-logo">✈ SkyTix</div>
                <ul class="menu">
                    <li><a href="home.php">Pagina Principală</a></li>
                    <li class="active"><a href="bookings.php">Rezervări</a></li>
                    <li><a href="flights.php">Zboruri</a></li>
                    <li><a href="payments.php">Plăți</a></li>
                    <li><a href="messages.php">Mesaje</a></li>
                    <li><a href="tracking.php">Urmărire Zboruri</a></li>
                    <li><a href="deals.php">Oferte</a></li>
                </ul>
            </aside>

            <main class="main">
                <div class="topbar">
                    <h2>Rezervări</h2>
                    
                </div>

                <section class="cards">
                    <div class="card">
                        <small>Total Rezervări</small>
                        <div class="value"><?= (int)($bookingStats['total_bookings'] ?? 0) ?></div>
                    </div>

                    <div class="card">
                        <small>Rezervări Plătite</small>
                        <div class="value"><?= (int)($bookingStats['paid_bookings'] ?? 0) ?></div>
                    </div>

                    <div class="card">
                        <small>Rezervări în Așteptare</small>
                        <div class="value"><?= (int)($bookingStats['pending_bookings'] ?? 0) ?></div>
                    </div>

                    <div class="card">
                        <small>Rezervări Anulate</small>
                        <div class="value"><?= (int)($bookingStats['cancelled_bookings'] ?? 0) ?></div>
                    </div>
                </section>

                <section class="table-card">
                    <div class="table-header">
                        <div class="table-title">Lista Rezervarilor</div>

                        <?php if ($isAdmin): ?>
                            <a href="add_booking.php" class="add-btn">+ Adaugă</a>
                        <?php endif; ?>
                    </div>

                    <div class="filters-bar">
    <input type="text" id="searchInput" placeholder="Caută utilizator, email, zbor, rută...">

    <select id="statusFilter">
        <option value="">Toate stările</option>
        <option value="paid">Plătită</option>
        <option value="pending">În așteptare</option>
        <option value="cancelled">Anulată</option>
    </select>

    <select id="companyFilter">
        <option value="">Toate companiile</option>
        <?php
        $companies = [];
        foreach ($bookings as $booking) {
            $companies[$booking['airline_name']] = $booking['airline_name'];
        }
        foreach ($companies as $company):
        ?>
            <option value="<?= strtolower(htmlspecialchars($company)) ?>">
                <?= htmlspecialchars($company) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                    <?php if (!empty($bookings)): ?>
                        <table class="bookings-table">
                            <thead>
                                <tr 
    data-search="<?= strtolower(htmlspecialchars($booking['user_name'] . ' ' . $booking['email'] . ' ' . $booking['flight_number'] . ' ' . $booking['origin_code'] . ' ' . $booking['destination_code'])) ?>"
    data-status="<?= strtolower(htmlspecialchars($booking['status'])) ?>"
    data-company="<?= strtolower(htmlspecialchars($booking['airline_name'])) ?>"
>
                                    <th>ID</th>
                                    <th>Utilizator</th>
                                    <th>Email</th>
                                    <th>Zbor</th>
                                    <th>Companie</th>
                                    <th>Rută</th>
                                    <th>Preț</th>
                                    <th>Stare</th>
                                    <th>Data Rezervării</th>
                                    <?php if ($isAdmin): ?>
                                        <th>Acțiuni</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr 
    data-search="<?= strtolower(htmlspecialchars($booking['user_name'] . ' ' . $booking['email'] . ' ' . $booking['flight_number'] . ' ' . $booking['origin_code'] . ' ' . $booking['destination_code'])) ?>"
    data-status="<?= strtolower(htmlspecialchars($booking['status'])) ?>"
    data-company="<?= strtolower(htmlspecialchars($booking['airline_name'])) ?>"
>
                                        <td>#<?= (int)$booking['id'] ?></td>
                                        <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['email']) ?></td>
                                        <td><?= htmlspecialchars($booking['flight_number']) ?></td>
                                        <td><?= htmlspecialchars($booking['airline_name']) ?></td>
                                        <td class="route">
                                            <?= htmlspecialchars($booking['origin_code']) ?> → <?= htmlspecialchars($booking['destination_code']) ?>
                                        </td>
                                        <td class="price"><?= number_format((float)$booking['price'], 2) ?> RON</td>
                                        <td>
                                            <?php
                                            $status = strtolower($booking['status']);
                                            $statusClass = 'status-pending';
                                            $statusText = 'În Așteptare';

                                            if ($status === 'paid') {
                                                $statusClass = 'status-paid';
                                                $statusText = 'Plătită';
                                            } elseif ($status === 'cancelled') {
                                                $statusClass = 'status-cancelled';
                                                $statusText = 'Anulată';
                                            }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($booking['created_at']) ?></td>

                                        <?php if ($isAdmin): ?>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('Sigur vrei să ștergi această rezervare?');">
                                                    <input type="hidden" name="delete_booking_id" value="<?= (int)$booking['id'] ?>">
                                                    <button type="submit" class="delete-btn">Șterge</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-text">Nu există încă rezervări în baza de date.</div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>
    <script>
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const companyFilter = document.getElementById('companyFilter');

    function filterBookings() {
        const searchValue = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const companyValue = companyFilter.value;

        const rows = document.querySelectorAll('.bookings-table tbody tr');

        rows.forEach(row => {
            const rowSearch = row.dataset.search || '';
            const rowStatus = row.dataset.status || '';
            const rowCompany = row.dataset.company || '';

            const matchesSearch = rowSearch.includes(searchValue);
            const matchesStatus = statusValue === '' || rowStatus === statusValue;
            const matchesCompany = companyValue === '' || rowCompany === companyValue;

            row.style.display = matchesSearch && matchesStatus && matchesCompany ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterBookings);
    statusFilter.addEventListener('change', filterBookings);
    companyFilter.addEventListener('change', filterBookings);
</script>
</body>
</html>