<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Elevation Applications</title>
    <style>
        :root {
            --header-bg: #2D3E50;
            --content-bg: #F5F7FA;
            --card-bg: #FFFFFF;
            --text-dark: #34495E;
            --text-muted: #7F8C8D;
            --accent-green: #27AE60;
            --accent-red: #E74C3C;
            --accent-amber: #F39C12;
            --border-color: #e1e4e8;
        }
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #1c2833; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 2rem; }
        .elevation-container { width: 100%; max-width: 1024px; aspect-ratio: 16 / 9; background-color: var(--content-bg); border-radius: 8px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); display: flex; flex-direction: column; overflow: hidden; }
        .tabs-header { display: flex; background-color: var(--header-bg); flex-shrink: 0; }
        .tab { padding: 1rem 1.5rem; cursor: pointer; font-size: 1rem; font-weight: 600; color: #bdc3c7; border-bottom: 4px solid transparent; transition: all 0.2s ease-in-out; }
        .tab.active { color: var(--text-dark); background-color: var(--content-bg); border-bottom-color: var(--header-bg); }
        .tab:not(.active):hover { background-color: #34495E; color: #FFF; }
        .tab-content-area { flex-grow: 1; padding: 1.5rem; overflow-y: auto; }
        #admin-applications { display: none; }
        .application-card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 1.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 1.5rem; }
        .applicant-info { display: flex; flex-direction: column; align-items: center; text-align: center; flex-basis: 120px; flex-shrink: 0; }
        .applicant-avatar { width: 64px; height: 64px; border-radius: 50%; background-color: #dfe6e9; margin-bottom: 0.5rem; }
        .applicant-name { font-size: 1.1rem; font-weight: 600; color: var(--text-dark); }
        .applicant-role { font-size: 0.8rem; color: var(--text-muted); }
        .application-details { flex-grow: 1; }
        .application-details p { margin: 0.4rem 0; color: var(--text-dark); }
        .major { font-weight: 500; }
        .statement { font-style: italic; color: var(--text-muted); border-left: 3px solid var(--border-color); padding-left: 0.75rem; }
        .date { font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem; }
        .action-buttons { display: flex; flex-direction: column; gap: 0.75rem; }
        .admin-application-card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 1rem 1.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 2rem; }
        .nominator-info { text-align: center; flex-basis: 150px; flex-shrink: 0; }
        .nominator-info .applicant-avatar { margin-left: auto; margin-right: auto; }
        .nominator-info .applicant-role { font-size: 0.9rem; }
        .admin-details { flex-grow: 1; }
        .admin-details p { display: flex; align-items: center; gap: 0.75rem; margin: 0.5rem 0; font-size: 0.9rem; color: var(--text-dark); }
        .admin-details svg { width: 16px; height: 16px; fill: var(--text-muted); flex-shrink: 0; }
        .verification-actions { flex-basis: 220px; flex-shrink: 0; text-align: center; }
        .verification-status { display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--accent-amber); margin-bottom: 1rem; }
        .docs-link { display: block; margin-bottom: 1rem; font-size: 0.9rem; color: #3498db; text-decoration: none; }
        .docs-link:hover { text-decoration: underline; }
        .admin-actions { display: flex; gap: 0.5rem; justify-content: center;}
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 5px; font-size: 0.9rem; font-weight: 600; cursor: pointer; color: white; transition: opacity 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 0.4rem; }
        .btn:hover { opacity: 0.85; }
        .btn-approve { background-color: var(--accent-green); }
        .btn-reject { background-color: var(--accent-red); }
    </style>
</head>
<body>

<?php
    // --- Dummy Data for Moderator Applications ---
    $moderator_applications = [
        ['name' => 'Damith', 'university' => 'University of Colombo', 'major' => 'Computer Science (Year 3)', 'date' => 'Dec 10, 2024'],
        ['name' => 'Naveen', 'university' => 'University of Moratuwa', 'major' => 'Software Engineering (Year 2)', 'date' => 'Dec 9, 2024'],
        ['name' => 'Fathima', 'university' => 'SLIIT', 'major' => 'Information Technology (Year 4)', 'date' => 'Dec 9, 2024'],
        ['name' => 'Kasun', 'university' => 'University of Peradeniya', 'major' => 'Engineering (Year 3)', 'date' => 'Dec 8, 2024'],
        ['name' => 'Priya', 'university' => 'University of Kelaniya', 'major' => 'Statistics (Year 2)', 'date' => 'Dec 8, 2024'],
        ['name' => 'Ravi', 'university' => 'University of Sri Jayewardenepura', 'major' => 'Business Management (Year 4)', 'date' => 'Dec 7, 2024'],
        ['name' => 'Ishani', 'university' => 'NSBM Green University', 'major' => 'Computer Networks (Year 3)', 'date' => 'Dec 7, 2024'],
        ['name' => 'Sanjay', 'university' => 'University of Ruhuna', 'major' => 'Marine Science (Year 2)', 'date' => 'Dec 6, 2024'],
        ['name' => 'Anusha', 'university' => 'University of Moratuwa', 'major' => 'Data Science (Year 2)', 'date' => 'Dec 5, 2024'],
        ['name' => 'Mihiran', 'university' => 'University of Colombo (UCSC)', 'major' => 'Information Systems (Year 3)', 'date' => 'Dec 5, 2024'],
    ];

    // --- Dummy Data for Admin Applications ---
    $admin_applications = [
        ['nominator' => 'Prof. Kamal Perera', 'applicant' => 'Dr. Anjali Wickramasinghe', 'role' => 'Lecturer, Faculty of Engineering', 'university' => 'University of Moratuwa', 'email' => 'anjali.w@eng.mrt.ac.lk'],
        ['nominator' => 'Prof. Sunethra Silva', 'applicant' => 'Dr. Roshan Jayasuriya', 'role' => 'Senior Lecturer, UCSC', 'university' => 'University of Colombo', 'email' => 'roshan.j@ucsc.cmb.ac.lk'],
        ['nominator' => 'Dr. Nimal Rajapaksa', 'applicant' => 'Ms. Shalini Fernando', 'role' => 'Asst. Lecturer, Faculty of IT', 'university' => 'SLIIT', 'email' => 'shalini.f@sliit.lk'],
        ['nominator' => 'Prof. Indika Karunaratne', 'applicant' => 'Dr. Bimsara Herath', 'role' => 'Head of Department, Engineering', 'university' => 'University of Peradeniya', 'email' => 'bimsara.h@eng.pdn.ac.lk'],
        ['nominator' => 'Dr. Geetha Kumarage', 'applicant' => 'Prof. Asoka Dissanayake', 'role' => 'Professor, Faculty of Science', 'university' => 'University of Kelaniya', 'email' => 'asoka.d@sci.kln.ac.lk'],
        ['nominator' => 'Prof. Rohan Samarajiva', 'applicant' => 'Dr. Lakmal Senanayake', 'role' => 'Senior Lecturer, Management', 'university' => 'University of Sri Jayewardenepura', 'email' => 'lakmal.s@sjp.ac.lk'],
        ['nominator' => 'Dr. Ajantha Athukorala', 'applicant' => 'Ms. Thilini Weerasinghe', 'role' => 'Lecturer, School of Computing', 'university' => 'NSBM Green University', 'email' => 'thilini.w@nsbm.ac.lk'],
        ['nominator' => 'Prof. Tilak Hettiarachchy', 'applicant' => 'Dr. Saman Kumara', 'role' => 'Senior Lecturer, Faculty of Science', 'university' => 'University of Ruhuna', 'email' => 'saman.k@sci.ruh.ac.lk'],
        ['nominator' => 'Prof. Gihan Dias', 'applicant' => 'Dr. Ruwan Gamage', 'role' => 'Lecturer, Dept. of CSE', 'university' => 'University of Moratuwa', 'email' => 'ruwan.g@cse.mrt.ac.lk'],
        ['nominator' => 'Dr. Prasad Wimalaratne', 'applicant' => 'Dr. Nayani Kodagoda', 'role' => 'Senior Lecturer, UCSC', 'university' => 'University of Colombo', 'email' => 'nayani.k@ucsc.cmb.ac.lk'],
    ];
?>

    <div class="elevation-container">
        <div class="tabs-header">
            <div class="tab active" data-target="moderator-applications">Moderator Applications</div>
            <div class="tab" data-target="admin-applications">Admin Applications</div>
        </div>

        <div class="tab-content-area">
            <div id="moderator-applications">
                <?php foreach ($moderator_applications as $app): ?>
                <div class="application-card">
                    <div class="applicant-info">
                        <div class="applicant-avatar"></div>
                        <div class="applicant-name"><?= htmlspecialchars($app['name']) ?></div>
                        <div class="applicant-role">Undergraduate at <?= htmlspecialchars($app['university']) ?></div>
                    </div>
                    <div class="application-details">
                        <p class="major"><?= htmlspecialchars($app['major']) ?></p>
                        <p class="statement">"I am active daily and would be honored to contribute to the platform's safety..."</p>
                        <p class="date">Applied on: <?= htmlspecialchars($app['date']) ?></p>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-approve">Approve</button>
                        <button class="btn btn-reject">Reject</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="admin-applications">
                <?php foreach ($admin_applications as $app): ?>
                <div class="admin-application-card">
                    <div class="nominator-info">
                        <div class="applicant-avatar"></div>
                        <p class="applicant-name" style="margin-bottom: 0.2rem;"><?= htmlspecialchars($app['nominator']) ?></p>
                        <p class="applicant-role" style="margin-top:0;">Nominator</p>
                    </div>
                    <div class="admin-details">
                        <p><svg viewBox="0 0 20 20"><path d="M10 12c-3.314 0-6 2.686-6 6h12c0-3.314-2.686-6-6-6zM10 10c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4z"></path></svg> <strong>Applicant:</strong> <?= htmlspecialchars($app['applicant']) ?></p>
                        <p><svg viewBox="0 0 20 20"><path d="M16 4h-3v-1c0-1.657-1.343-3-3-3h-2c-1.657 0-3 1.343-3 3v1h-3c-1.1 0-2 0.9-2 2v10c0 1.1 0.9 2 2 2h14c1.1 0 2-0.9 2-2v-10c0-1.1-0.9-2-2-2zM8 3c0-0.552 0.448-1 1-1h2c0.552 0 1 0.448 1 1v1h-4v-1z"></path></svg> <strong>Role:</strong> <?= htmlspecialchars($app['role']) ?></p>
                        <p><svg viewBox="0 0 20 20"><path d="M18 9v10l-8-4-8 4v-10l8-4zM2 7.556v-3.556h16v3.556l-8 4z"></path></svg> <strong>University:</strong> <?= htmlspecialchars($app['university']) ?></p>
                        <p><svg viewBox="0 0 20 20"><path d="M18 4h-16c-1.1 0-2 0.9-2 2v10c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2v-10c0-1.1-0.9-2-2-2zM18 6l-8 5-8-5v0z"></path></svg> <strong>Email:</strong> <?= htmlspecialchars($app['email']) ?></p>
                    </div>
                    <div class="verification-actions">
                        <a href="#" class="docs-link">View Supporting Documents</a>
                        <div class="admin-actions">
                            <button class="btn btn-approve">APPROVE</button>
                            <button class="btn btn-reject">REJECT <svg fill="white" width="12px" height="12px" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"></path></svg></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.tab');
            const contentPanes = document.querySelectorAll('.tab-content-area > div');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    contentPanes.forEach(pane => { pane.style.display = 'none'; });
                    tab.classList.add('active');
                    const targetPane = document.getElementById(tab.getAttribute('data-target'));
                    if (targetPane) { targetPane.style.display = 'block'; }
                });
            });
        });
    </script>
</body>
</html>