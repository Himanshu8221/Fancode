<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fancode Live Matches</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #222;
    color: #fff;
    padding: 20px;
}
h1 { text-align: center; margin-bottom: 10px; }
.playlist-button {
    display: block;
    width: 200px;
    margin: 0 auto 20px auto;
    padding: 10px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1em;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
}
.playlist-button:hover {
    background-color: #0069d9;
}
.search-container { text-align: center; margin-bottom: 20px; }
input[type="text"] {
    padding: 10px;
    width: 100%;
    max-width: 400px;
    border-radius: 5px;
    border: none;
    font-size: 16px;
    box-sizing: border-box;
}
.channels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 20px;
    max-width: 1200px;
    margin: auto;
}
.channel-card {
    position: relative;
    background-color: #333;
    border-radius: 5px;
    overflow: hidden;
    text-align: center;
    padding: 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.channel-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
}
.channel-logo-container {
    width: 94%;
    padding-top: 56.25%;
    position: relative;
    background-color: #111;
    border: 5px solid #555;
}
.channel-logo {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.channel-name {
    font-size: 1em;
    margin-top: 10px;
    word-wrap: break-word;
    flex-grow: 1;
}
.badge {
    position: absolute;
    top: 8px;
    left: 8px;
    color: white;
    font-size: 0.7em;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
    white-space: nowrap;
    transition: background-color 0.2s ease;
    z-index: 10;
}
.live-badge { background-color: #d9534f; animation: pulse 1s infinite; }
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(217, 83, 79, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(217, 83, 79, 0); }
    100% { box-shadow: 0 0 0 0 rgba(217, 83, 79, 0); }
}
.upcoming-badge { background-color: #f0ad4e; }
.upcoming-time-container { display: flex; flex-direction: column; align-items: center; margin-top: 8px; }
.upcoming-time { padding: 4px 6px; font-size: 0.9em; color: #aaa; }
.watch-button {
    margin-top: 10px;
    padding: 6px 12px;
    background-color: #5cb85c;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background-color 0.2s ease;
}
.watch-button:hover { background-color: #4cae4c; }
a { text-decoration: none; color: inherit; }
@media (max-width: 600px) {
    .channel-logo-container { padding-top: 56.25%; }
    .badge { font-size: 0.6em; padding: 1px 4px; }
    .watch-button { font-size: 0.8em; padding: 4px 8px; }
    .playlist-button { width: 100%; }
}
</style>
</head>
<body>

<h1>Fancode Live Matches</h1>
<a href="./playlist.php" class="playlist-button" >📺Playlist For Ott Player</a>

<div class="search-container">
    <input type="text" id="search" placeholder="Search match by name...">
</div>
<div id="channels-container" class="channels-grid">
    <p id="loading-message">Loading matches...</p>
</div>

<script>
function parseTime(timeStr) {
    timeStr = timeStr.replace(/\s+/g, ' ').trim();
    const [time, meridiem, date] = timeStr.split(' ');
    if (!time || !meridiem || !date) return new Date(NaN);
    const [hours, minutes, seconds] = time.split(':').map(Number);
    const [day, month, year] = date.split('-').map(Number);
    let h = hours;
    if (meridiem.toUpperCase() === 'PM' && hours < 12) h += 12;
    if (meridiem.toUpperCase() === 'AM' && hours === 12) h = 0;
    return new Date(year, month - 1, day, h, minutes, seconds);
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('channels-container');
    const loadingMessage = document.getElementById('loading-message');
    const searchInput = document.getElementById('search');
    const jsonUrl = 'https://raw.githubusercontent.com/drmlive/fancode-live-events/main/fancode.json';
    let matches = [];

    function renderMatches(filter = '') {
        container.innerHTML = '';
        const filtered = matches.filter(m => m.title.toLowerCase().includes(filter.toLowerCase()));
        if (filtered.length === 0) {
            container.innerHTML = '<p>No matches found.</p>';
            return;
        }

        const now = new Date();

        filtered.forEach(match => {
            const card = document.createElement('div');
            card.className = 'channel-card';
            const streamUrl = match.adfree_url;
            const matchTime = parseTime(match.startTime);

            // Badge logic
            if (!isNaN(matchTime.getTime())) {
                const badge = document.createElement('div');
                if (matchTime <= now) {
                    badge.className = 'badge live-badge';
                    badge.textContent = 'LIVE';
                } else {
                    badge.className = 'badge upcoming-badge';
                    badge.textContent = 'UPCOMING';
                }
                card.appendChild(badge);
            }

            // Logo
            const logoContainer = document.createElement('div');
            logoContainer.className = 'channel-logo-container';
            const img = document.createElement('img');
            img.src = match.src;
            img.alt = match.title;
            img.className = 'channel-logo';
            img.onerror = () => { img.src = 'https://via.placeholder.com/150x80?text=No+Logo'; };
            logoContainer.appendChild(img);
            card.appendChild(logoContainer);

            // Title
            const h2 = document.createElement('h2');
            h2.className = 'channel-name';
            h2.textContent = match.title;
            card.appendChild(h2);

            // Upcoming time
            if (matchTime > now) {
                const upcomingTimeContainer = document.createElement('div');
                upcomingTimeContainer.className = 'upcoming-time-container';
                const upcomingTime = document.createElement('span');
                upcomingTime.className = 'upcoming-time';
                upcomingTime.textContent = match.startTime;
                upcomingTimeContainer.appendChild(upcomingTime);
                card.appendChild(upcomingTimeContainer);
            }

            // Watch Live button
            if (matchTime <= now && streamUrl) {
                const button = document.createElement('button');
                button.className = 'watch-button';
                button.textContent = '▶ Watch Live';
                button.addEventListener('click', e => {
                    e.stopPropagation();
                    window.location.href = `player.php?stream=${encodeURIComponent(streamUrl)}`;
                });
                card.appendChild(button);
            }

            // Card click
            card.addEventListener('click', () => {
                if (matchTime <= now && streamUrl) {
                    window.location.href = `player.php?stream=${encodeURIComponent(streamUrl)}`;
                }
            });

            container.appendChild(card);
        });
    }

    fetch(jsonUrl)
        .then(res => res.json())
        .then(data => {
            matches = data.matches || [];
            loadingMessage.style.display = 'none';
            renderMatches();
            setInterval(() => renderMatches(searchInput.value), 60000);
            searchInput.addEventListener('input', e => renderMatches(e.target.value));
        })
        .catch(err => {
            console.error('Error loading JSON:', err);
            loadingMessage.textContent = 'Failed to load matches. Check JSON URL.';
        });
});
</script>

</body>
</html>
