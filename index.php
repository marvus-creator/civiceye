<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicEye | Report Issues</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* --- BULLETPROOF POSITIONING & Z-INDEX FIXES --- */
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }

        /* The Map stays at the bottom (z-index 1) */
        #map { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }

        /* The Header sits on top (z-index 1000) */
        header { 
            position: absolute; top: 0; left: 0; width: 100%; 
            background: rgba(255, 255, 255, 0.95); /* Slight glass effect */
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000; 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 10px 20px; box-sizing: border-box; backdrop-filter: blur(5px);
        }

        .logo { font-size: 1.5rem; font-weight: bold; color: #1e293b; }

        /* Header Buttons */
        .header-controls { display: flex; gap: 10px; align-items: center; }
        .control-btn { 
            background: white; border: 2px solid #2563eb; color: #2563eb; 
            padding: 8px 15px; border-radius: 20px; font-weight: bold; 
            cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: 0.2s; 
        }
        .control-btn:hover { background: #2563eb; color: white; }
        .control-btn.active { background: #2563eb; color: white; }
        
        .status { font-weight: bold; color: #ef4444; display: flex; align-items: center; gap: 5px; font-size: 0.9rem; }
        .pulse { width: 10px; height: 10px; background: #ef4444; border-radius: 50%; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }

        /* The Main Report Button sits at the bottom (z-index 1000) */
        .btn-report-main {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
            background: #2563eb; color: white; padding: 15px 40px; border-radius: 30px;
            font-size: 1.2rem; font-weight: bold; border: none; cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4); z-index: 1000; transition: 0.2s;
        }
        .btn-report-main:hover { transform: translateX(-50%) scale(1.05); background: #1d4ed8; }

        /* The Modal covers everything (z-index 9999) */
        .modal { z-index: 9999; }
        
        .upvote-btn { background: #f8fafc; border: 1px solid #cbd5e1; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; width: 100%; color: #334155;}
        .upvote-btn:hover { background: #e2e8f0; }
    </style>
</head>
<body>

    <header>
        <div class="logo">CivicEye 👁️</div>
        
        <div class="header-controls">
            <button id="btn-heatmap" class="control-btn" onclick="toggleHeatmap()">🔥 Heatmap Off</button>
            <button id="btn-lang" class="control-btn" onclick="toggleLanguage()">🇷🇼 Kinyarwanda</button>
            <div class="status"><span class="pulse"></span><span id="txt-live">LIVE MONITORING</span></div>
        </div>
    </header>

    <div id="map"></div>

    <button class="btn-report-main" onclick="startReport()" id="btn-main-report">+ REPORT ISSUE</button>

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="margin:0; color:var(--dark);" id="txt-modal-title">New Incident Report</h2>
                <button class="close-btn" onclick="closeModal('reportModal')">×</button>
            </div>
            
            <form id="reportForm">
                <input type="hidden" id="lat" name="lat">
                <input type="hidden" id="lng" name="lng">
                <input type="hidden" id="category" name="category">

                <label id="txt-label-type">Incident Type</label>
                <div class="category-grid">
                    <div class="cat-option" onclick="selectCat('pothole', this)"><span style="font-size:1.5rem">🚧</span><br><span class="cat-txt" data-en="Pothole" data-rw="Ikinogo">Pothole</span></div>
                    <div class="cat-option" onclick="selectCat('trash', this)"><span style="font-size:1.5rem">🗑️</span><br><span class="cat-txt" data-en="Trash" data-rw="Imyanda">Trash</span></div>
                    <div class="cat-option" onclick="selectCat('streetlight', this)"><span style="font-size:1.5rem">💡</span><br><span class="cat-txt" data-en="Light Out" data-rw="Amatara Yapfuye">Light Out</span></div>
                    <div class="cat-option" onclick="selectCat('accident', this)"><span style="font-size:1.5rem">🚑</span><br><span class="cat-txt" data-en="Accident" data-rw="Impanuka">Accident</span></div>
                    <div class="cat-option" onclick="selectCat('water', this)"><span style="font-size:1.5rem">💧</span><br><span class="cat-txt" data-en="Water Leak" data-rw="Amazi Yamenetse">Water Leak</span></div>
                    <div class="cat-option" onclick="selectCat('vandalism', this)"><span style="font-size:1.5rem">🎨</span><br><span class="cat-txt" data-en="Vandalism" data-rw="Kwangiza">Vandalism</span></div>
                    <div class="cat-option" onclick="selectCat('parking', this)"><span style="font-size:1.5rem">🚗</span><br><span class="cat-txt" data-en="Bad Parking" data-rw="Guparika NABI">Bad Parking</span></div>
                    <div class="cat-option" onclick="selectCat('tree', this)"><span style="font-size:1.5rem">🌳</span><br><span class="cat-txt" data-en="Fallen Tree" data-rw="Igiti Cyaguye">Fallen Tree</span></div>
                </div>

                <label id="txt-label-desc">Description</label>
                <textarea name="description" rows="3" placeholder="Describe the issue..." required></textarea>

                <label id="txt-label-email">Email (To get updates when fixed - Optional)</label>
                <input type="email" name="email" placeholder="your@email.com" style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #cbd5e1; border-radius:5px; box-sizing: border-box;">

                <label id="txt-label-photo">Evidence (Photo)</label>
                <input type="file" name="image" accept="image/*">

                <button type="submit" class="btn-submit" id="btn-submit-form">SUBMIT REPORT</button>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
    <script src="js/main.js"></script>
</body>
</html>