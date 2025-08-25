<?php
// player.php

// Capture stream parameter if passed in URL
$stream = isset($_GET['stream']) ? $_GET['stream'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>M3U8 Player</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #1e293b, #0f172a);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 20px;
      color: white;
    }
    .video-container {
      width: 100%;
      max-width: 1000px;
      border-radius: 1rem;
      overflow: hidden;
      background: #000;
      box-shadow: 0 10px 40px rgba(0,0,0,0.6);
    }
    .control-bar {
      backdrop-filter: blur(12px);
      background: rgba(30, 41, 59, 0.7);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 9999px;
      padding: 8px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    }
    select {
      background-color: rgba(15,23,42,0.8);
      color: white;
      border-radius: 0.5rem;
      padding: 6px 10px;
      font-size: 14px;
      border: 1px solid rgba(255,255,255,0.2);
    }
    select:focus {
      outline: none;
      border-color: #38bdf8;
    }
  </style>
</head>
<body>

  <div class="video-container">
    <video id="video" controls playsinline class="w-full aspect-video"></video>
  </div>

  <!-- Control Bar -->
  <div class="control-bar">
    <span class="text-gray-300 text-sm font-semibold">Quality</span>
    <select id="quality-selector"></select>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
  <script>
    const video = document.getElementById("video");
    const qualitySelector = document.getElementById("quality-selector");

    // Stream passed via PHP
    const videoSrcRaw = <?php echo $stream ? json_encode($stream) : 'null'; ?>;

    // CONSTANT PROXY URL
    const PROXY_URL = "./proxy.php?url=";

    // Prepend proxy if videoSrcRaw exists
    const videoSrc = videoSrcRaw ? PROXY_URL + encodeURIComponent(videoSrcRaw) : null;

    let hls, levels = [];

    function setQuality(index) {
      if (!levels.length) return;
      hls.currentLevel = (index === "auto") ? -1 : parseInt(index);
    }

    if (videoSrc) {
      if (Hls.isSupported()) {
        hls = new Hls();
        hls.loadSource(videoSrc);
        hls.attachMedia(video);

        hls.on(Hls.Events.MANIFEST_PARSED, (_, data) => {
          levels = data.levels;
          qualitySelector.innerHTML = "";

          let autoOption = document.createElement("option");
          autoOption.value = "auto";
          autoOption.text = "Auto";
          qualitySelector.appendChild(autoOption);

          levels.forEach((lvl, i) => {
            const option = document.createElement("option");
            option.value = i;
            option.text = lvl.height ? `${lvl.height}p` : `${Math.round(lvl.bitrate/1000)} kbps`;
            qualitySelector.appendChild(option);
          });

          qualitySelector.value = "auto";
        });

        hls.on(Hls.Events.ERROR, (_, data) => console.error("HLS.js error:", data));
        qualitySelector.addEventListener("change", (e) => setQuality(e.target.value));

      } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
        video.src = videoSrc;
      } else {
        console.error("HLS not supported in this browser.");
      }
    } else {
      console.error("No ?stream= URL provided.");
    }

    // Keyboard Shortcuts
    document.addEventListener("keydown", (e) => {
      if (["input","textarea","select"].includes(e.target.tagName.toLowerCase())) return;
      switch(e.key) {
        case " ": e.preventDefault(); video.paused ? video.play() : video.pause(); break;
        case "f": video.requestFullscreen(); break;
        case "m": video.muted = !video.muted; break;
      }
    });
  </script>
</body>
</html>
