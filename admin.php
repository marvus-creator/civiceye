<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicEye | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #e2e8f0; color: #64748b; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .status-open { background: #fee2e2; color: #ef4444; }
        .status-resolved { background: #d1fae5; color: #10b981; }
        .thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; cursor: pointer; transition: 0.2s; }
        .thumb:hover { transform: scale(1.1); }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn-resolve { background: #10b981; color: white; }
        .btn-resolve:hover { background: #059669; }
        .btn-nuke { background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .btn-nuke:hover { background: #dc2626; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }
        
        /* New Upvote Badge for Admin */
        .vote-badge { background: #f59e0b; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; margin-left: 10px;}
        
        #imgModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        #fullImage { max-width: 90%; max-height: 90%; border-radius: 5px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
    </style>
</head>
<body>

    <div class="header">
        <h1>🏙️ City Operations Center</h1>
        <div style="display:flex; gap: 15px; align-items:center;">
            <a href="index.php" style="text-decoration:none; color:#2563eb; font-weight:bold;">← Back to Map</a>
            <button class="btn-nuke" onclick="resetDatabase()">🗑️ Clear All Data</button>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Evidence</th>
                    <th>Issue & Priority</th>
                    <th>Reporter Info</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="reportTable"></tbody>
        </table>
    </div>

    <div id="imgModal" onclick="this.style.display='none'">
        <img id="fullImage">
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", loadReports);

        async function loadReports() {
            const res = await fetch('api/reports.php');
            const reports = await res.json();
            const tbody = document.getElementById('reportTable');
            tbody.innerHTML = '';

            reports.forEach(report => {
                const statusClass = report.status === 'resolved' ? 'status-resolved' : 'status-open';
                
                const actionBtn = report.status === 'open' 
                    ? `<button class="btn btn-resolve" onclick="resolveIssue(${report.id})">✔ Mark Fixed (Sends Email)</button>`
                    : `<span style="color:#10b981; font-weight:bold;">✅ FIXED</span>`;

                const img = report.image_path 
                    ? `<img src="${report.image_path}" class="thumb" onclick="showImg('${report.image_path}')">`
                    : '<span style="color:#ccc">No Img</span>';

                let icon = '⚠️';
                if(report.category === 'pothole') icon = '🚧';
                if(report.category === 'trash') icon = '🗑️';
                if(report.category === 'streetlight') icon = '💡';
                if(report.category === 'accident') icon = '🚑';
                if(report.category === 'water') icon = '💧';
                if(report.category === 'vandalism') icon = '🎨';
                if(report.category === 'parking') icon = '🚗';
                if(report.category === 'tree') icon = '🌳';

                const catText = report.category ? report.category : 'Unknown';
                
                // Show Upvotes!
                const voteBadge = report.upvotes > 0 ? `<span class="vote-badge">🔥 ${report.upvotes} Votes</span>` : '';
                
                // Show Email!
                const emailDisplay = report.email ? `<span style="color:#2563eb; font-size:0.85rem;">📧 ${report.email}</span><br>` : '';

                const row = `
                    <tr>
                        <td style="color:#64748b; font-size:0.9rem;">#${report.id}</td>
                        <td>${img}</td>
                        <td style="font-weight:bold;">${icon} <span style="text-transform:capitalize;">${catText}</span> ${voteBadge}</td>
                        <td style="color:#475569;">${emailDisplay}${report.description}</td>
                        <td><span class="status-badge ${statusClass}">${report.status}</span></td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        async function resolveIssue(id) {
            if(!confirm("Are you sure this issue has been resolved?\n(If they left an email, they will be notified!)")) return;
            await fetch('api/reports.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'resolve', id: id })
            });
            loadReports(); 
        }

        async function resetDatabase() {
            if(!confirm("⚠️ WARNING: This will DELETE ALL REPORTS forever.\n\nAre you sure you want to clear the database?")) return;
            await fetch('api/reports.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'reset' }) });
            alert("Database Cleared! 🧹"); loadReports();
        }

        function showImg(src) {
            document.getElementById('fullImage').src = src;
            document.getElementById('imgModal').style.display = 'flex';
        }
    </script>
</body>
</html>