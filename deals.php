<?php
session_start();
require_once 'db.php';

$offersSql = "
    SELECT
        f.id,
        f.flight_number,
        f.base_price,
        f.status,
        a.name AS airline_name,
        ao.iata_code AS origin_code,
        ao.city AS origin_city,
        ad.iata_code AS destination_code,
        ad.city AS destination_city
    FROM flights f
    JOIN airlines a ON f.airline_id = a.id
    JOIN airports ao ON f.origin_airport_id = ao.id
    JOIN airports ad ON f.destination_airport_id = ad.id
    WHERE f.status = 'Active'
    ORDER BY f.base_price ASC
    LIMIT 6
";

$result = $conn->query($offersSql);
$offers = [];

while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}

$offersJson = json_encode($offers);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>SkyTix - Oferte</title>

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

.menu li.active a {
    background: #e4d09c;
    color: #1f1f1f;
    font-weight: 700;
}

.main {
    padding: 26px;
}

.topbar h2 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 24px;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 22px;
    align-items: start;
}

.offers-card,
.chat-card {
    background: white;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.04);
}

.section-title {
    font-size: 26px;
    font-weight: 800;
    margin-bottom: 8px;
}

.section-subtitle {
    color: #8a8a8a;
    margin-bottom: 22px;
}

.offer-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.offer-item {
    background: #fafafa;
    border-radius: 18px;
    padding: 18px;
    border: 1px solid #eeeeee;
}

.offer-route {
    font-size: 28px;
    font-weight: 900;
    margin-bottom: 8px;
}

.offer-airline {
    color: #8a8a8a;
    margin-bottom: 14px;
}

.offer-price {
    font-size: 22px;
    font-weight: 900;
    color: #1f1f1f;
}

.offer-badge {
    display: inline-block;
    margin-top: 12px;
    background: #e4d09c;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 800;
}

.chat-card {
    height: fit-content;
}

.chat-box {
    height: 500px;
    background: #f7f7f7;
    border-radius: 18px;
    padding: 16px;
    overflow-y: auto;
    margin-bottom: 16px;
}

.message {
    margin-bottom: 12px;
    max-width: 85%;
    padding: 12px 14px;
    border-radius: 16px;
    line-height: 1.4;
    font-size: 14px;
}

.bot {
    background: #ffffff;
    color: #1f1f1f;
    border-top-left-radius: 4px;
}

.user {
    background: #e4d09c;
    color: #1f1f1f;
    margin-left: auto;
    border-top-right-radius: 4px;
    font-weight: 600;
}

.chat-input-row {
    display: flex;
    gap: 10px;
}

.chat-input-row input {
    flex: 1;
    border: 1px solid #dddddd;
    border-radius: 14px;
    padding: 12px 14px;
    font-size: 14px;
    outline: none;
}

.chat-input-row button {
    border: none;
    background: #d8b75b;
    border-radius: 14px;
    padding: 12px 16px;
    font-weight: 800;
    cursor: pointer;
}

.quick-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 14px;
}

.quick-buttons button {
    border: none;
    background: #efefef;
    border-radius: 999px;
    padding: 8px 12px;
    cursor: pointer;
    font-weight: 700;
}

.quick-buttons button:hover {
    background: #e4d09c;
}

.empty-text {
    color: #8a8a8a;
}

@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .offer-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 850px) {
    .dashboard-shell {
        grid-template-columns: 1fr;
    }

    .sidebar {
        min-height: auto;
    }
}
</style>
</head>

<body>
<div class="page-wrapper">
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="sidebar-logo">✈ SkyTix</div>

            <ul class="menu">
                <li><a href="home.php">Pagina Principala</a></li>
                <li><a href="bookings.php">Rezervari</a></li>
                <li><a href="flights.php">Zboruri</a></li>
                <li><a href="payments.php">Plati</a></li>
                <li><a href="messages.php">Mesaje</a></li>
                <li><a href="tracking.php">Urmarire Zboruri</a></li>
                <li class="active"><a href="deals.php">Oferte</a></li>
                <li><a href="telecom.php">Telecom</a></li>
            </ul>
        </aside>

        <main class="main">
            <div class="topbar">
                <h2>Oferte si Asistent Virtual</h2>
            </div>

            <div class="content-grid">
                <section class="offers-card">
                    <div class="section-title">Cele mai bune oferte</div>
                    <div class="section-subtitle">Zboruri active ordonate după cel mai mic pret.</div>

                    <?php if (!empty($offers)): ?>
                        <div class="offer-list">
                            <?php foreach ($offers as $offer): ?>
                                <div class="offer-item">
                                    <div class="offer-route">
                                        <?= htmlspecialchars($offer['origin_code']) ?> → <?= htmlspecialchars($offer['destination_code']) ?>
                                    </div>

                                    <div class="offer-airline">
                                        <?= htmlspecialchars($offer['origin_city']) ?> către <?= htmlspecialchars($offer['destination_city']) ?>
                                        · <?= htmlspecialchars($offer['airline_name']) ?>
                                    </div>

                                    <div class="offer-price">
                                        <?= number_format((float)$offer['base_price'], 2) ?> RON
                                    </div>

                                    <div class="offer-badge">
                                        <?= htmlspecialchars($offer['flight_number']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-text">Nu există oferte active momentan.</div>
                    <?php endif; ?>
                </section>

                <section class="chat-card">
                    <div class="section-title">ChatBot SkyTix</div>
                    <div class="section-subtitle">Intreabă despre oferte, zboruri sau preturi.</div>

                    <div class="quick-buttons">
                        <button onclick="quickAsk('Care este cea mai ieftină ofertă?')">Cea mai ieftina oferta</button>
                        <button onclick="quickAsk('Ce zboruri active există?')">Zboruri active</button>
                        <button onclick="quickAsk('Ce companii au oferte?')">Companii</button>
                    </div>

                    <div class="chat-box" id="chatBox">
                        <div class="message bot">
                            Bună! Sunt asistentul SkyTix. Te pot ajuta cu oferte, rute, companii aeriene si preturi.
                        </div>
                    </div>

                    <div class="chat-input-row">
                        <input type="text" id="chatInput" placeholder="Scrie un mesaj...">
                        <button onclick="sendMessage()">Trimite</button>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
const offers = <?= $offersJson ?>;

const chatBox = document.getElementById('chatBox');
const chatInput = document.getElementById('chatInput');

function addMessage(text, type) {
    const div = document.createElement('div');
    div.className = 'message ' + type;
    div.innerHTML = text;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function getCheapestOffer() {
    if (offers.length === 0) {
        return 'Momentan nu există oferte active.';
    }

    const offer = offers[0];

    return `
        Cea mai ieftină ofertă este <strong>${offer.origin_code} → ${offer.destination_code}</strong>,
        cu ${offer.airline_name}, la prețul de
        <strong>${parseFloat(offer.base_price).toFixed(2)} RON</strong>.
    `;
}

function getActiveFlights() {
    if (offers.length === 0) {
        return 'Nu există zboruri active momentan.';
    }

    let html = 'Zborurile active disponibile sunt:<br>';

    offers.forEach(offer => {
        html += `• <strong>${offer.flight_number}</strong>: ${offer.origin_code} → ${offer.destination_code}, ${parseFloat(offer.base_price).toFixed(2)} RON<br>`;
    });

    return html;
}

function getCompanies() {
    if (offers.length === 0) {
        return 'Nu există companii cu oferte active momentan.';
    }

    const companies = [...new Set(offers.map(o => o.airline_name))];

    return 'Companiile cu oferte active sunt: <strong>' + companies.join(', ') + '</strong>.';
}

function getRoutes() {
    if (offers.length === 0) {
        return 'Nu există rute active momentan.';
    }

    let html = 'Rutele disponibile sunt:<br>';

    offers.forEach(offer => {
        html += `• ${offer.origin_city} (${offer.origin_code}) → ${offer.destination_city} (${offer.destination_code})<br>`;
    });

    return html;
}

function botReply(message) {
    const msg = message.toLowerCase();

    if (msg.includes('ieftin') || msg.includes('cea mai ieftină') || msg.includes('ofertă')) {
        return getCheapestOffer();
    }

    if (msg.includes('zbor') || msg.includes('active')) {
        return getActiveFlights();
    }

    if (msg.includes('companie') || msg.includes('companii')) {
        return getCompanies();
    }

    if (msg.includes('rută') || msg.includes('rute') || msg.includes('destina')) {
        return getRoutes();
    }

    if (msg.includes('pret') || msg.includes('preț') || msg.includes('cost')) {
        return getCheapestOffer();
    }

    if (msg.includes('salut') || msg.includes('bună') || msg.includes('buna')) {
        return 'Bună! Îți pot arăta cele mai bune oferte, zborurile active, companiile disponibile sau rutele existente.';
    }

    const foundDestination = offers.filter(offer =>
    offer.destination_city.toLowerCase().includes(msg) ||
    offer.destination_code.toLowerCase().includes(msg) ||
    offer.origin_city.toLowerCase().includes(msg) ||
    offer.origin_code.toLowerCase().includes(msg)
);

    if (foundDestination.length > 0) {
        let html = `Am găsit următoarele zboruri pentru căutarea ta:<br>`;

        foundDestination.forEach(offer => {
            html += `• <strong>${offer.flight_number}</strong>: 
            ${offer.origin_city} (${offer.origin_code}) → 
            ${offer.destination_city} (${offer.destination_code}), 
            ${parseFloat(offer.base_price).toFixed(2)} RON<br>`;
        });

        return html;
    }

    for (const offer of offers) {
        const city = offer.destination_city.toLowerCase();
        const code = offer.destination_code.toLowerCase();

        if (msg.includes(city) || msg.includes(code)) {
            return `
                Da, există zbor către <strong>${offer.destination_city}</strong>:<br>
                • <strong>${offer.flight_number}</strong>: 
                ${offer.origin_code} → ${offer.destination_code}, 
                ${parseFloat(offer.base_price).toFixed(2)} RON
            `;
        }
    }

    for (const offer of offers) {
        const flightNumber = offer.flight_number.toLowerCase().replace(/\s+/g, '');
        const userMessage = msg.toLowerCase().replace(/\s+/g, '');

        if (userMessage.includes(flightNumber)) {
            return `
                Am găsit zborul <strong>${offer.flight_number}</strong>:<br>
                • Rută: <strong>${offer.origin_code} → ${offer.destination_code}</strong><br>
                • Companie: <strong>${offer.airline_name}</strong><br>
                • Preț: <strong>${parseFloat(offer.base_price).toFixed(2)} RON</strong><br><br>
                Pentru rezervare, mergi la pagina <strong>Rezervări</strong> și apasă <strong>+ Adauga</strong>.
            `;
        }
    }

    return 'Nu am înțeles complet întrebarea. Poți întreba despre: <strong>oferte</strong>, <strong>zboruri active</strong>, <strong>companii</strong>, <strong>rute</strong> sau <strong>prețuri</strong>.';
}

function sendMessage() {
    const text = chatInput.value.trim();

    if (text === '') return;

    addMessage(text, 'user');
    chatInput.value = '';

    setTimeout(() => {
        addMessage(botReply(text), 'bot');
    }, 400);
}

function quickAsk(text) {
    chatInput.value = text;
    sendMessage();
}

chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});
</script>

</body>
</html>