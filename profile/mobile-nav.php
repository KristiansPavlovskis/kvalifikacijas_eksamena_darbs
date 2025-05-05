<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout App</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        
        body {
            background-color: #171a21;
            color: #ffffff;
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
            position: relative;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            display: flex;
            align-items: center;
            padding: 20px 15px;
            border-bottom: 1px solid #282c34;
        }
        
        .back-button {
            margin-right: 15px;
            cursor: pointer;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .filter-section {
            display: flex;
            padding: 15px 10px;
            gap: 10px;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .filter-button {
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .filter-active {
            background-color: #e74c3c;
            color: white;
        }
        
        .filter-inactive {
            background-color: #282c34;
            color: white;
        }
        
        .main-content {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
        }
        
        .empty-workout {
            border: 2px dashed #444;
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .plus-icon {
            color: #e74c3c;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .workout-title {
            font-size: 22px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .workout-subtitle {
            color: #9aa0a6;
            font-size: 16px;
        }
        
        .workout-template {
            background-color: #282c34;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .template-title {
            font-size: 24px;
            font-weight: 500;
        }
        
        .edit-link {
            color: #e74c3c;
            text-decoration: none;
            font-size: 16px;
        }
        
        .template-details {
            display: flex;
            align-items: center;
            color: #9aa0a6;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }
        
        .icon {
            margin-right: 8px;
            opacity: 0.7;
        }
        
        .begin-button {
            background-color: #39414f;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 18px;
            width: 100%;
            font-size: 18px;
            margin-top: 20px;
            cursor: pointer;
        }
        
        .bottom-nav {
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            background-color: #171a21;
            border-top: 1px solid #282c34;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #9aa0a6;
            text-decoration: none;
            font-size: 12px;
        }
        
        .nav-icon {
            margin-bottom: 5px;
            font-size: 20px;
        }
        
        .active {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="back-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </div>
            <div class="header-title">Start Workout</div>
        </div>
        
        <div class="filter-section">
            <div class="filter-button filter-active">All</div>
            <div class="filter-button filter-inactive">Recent</div>
            <div class="filter-button filter-inactive">Favorites</div>
            <div class="filter-button filter-inactive">Upper Body</div>
            <div class="filter-button filter-inactive">Lo</div>
        </div>
        
        <div class="main-content">
            <div class="empty-workout">
                <div class="plus-icon">+</div>
                <div class="workout-title">Empty Workout</div>
                <div class="workout-subtitle">Start from scratch</div>
            </div>
            
            <div class="workout-template">
                <div class="template-header">
                    <div class="template-title">push day</div>
                    <a href="#" class="edit-link">edit template</a>
                </div>
                <div class="template-details">
                    <div class="detail-item">
                        <span class="icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 12h-8l-2-4-2 4H2"/>
                            </svg>
                        </span>
                        <span>8 exercises</span>
                    </div>
                    <div class="detail-item">
                        <span class="icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                        <span>45 min</span>
                    </div>
                    <div class="detail-item">
                        <span class="icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                        <span>Last: 2 days ago</span>
                    </div>
                </div>
            </div>
            
            <div class="workout-template">
                <div class="template-header">
                    <div class="template-title">pull day</div>
                    <a href="#" class="edit-link">edit template</a>
                </div>
                <div class="template-details">
                    <div class="detail-item">
                        <span class="icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 12h-8l-2-4-2 4H2"/>
                            </svg>
                        </span>
                        <span>8 exercises</span>
                    </div>
                    <div class="detail-item">
                        <span class="icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                        <span>45 min</span>
                    </div>
                    <div class="detail-item">
                        <span class="icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                        <span>Last: 2 days ago</span>
                    </div>
                </div>
            </div>
            
            <button class="begin-button">Begin Workout</button>
        </div>
        
        <div class="bottom-nav">
            <a href="#" class="nav-item active">
                <span class="nav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                </span>
                <span>Home</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </span>
                <span>Templates</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </span>
                <span>History</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                <span>Profile</span>
            </a>
        </div>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function() { 
            const filterButtons = document.querySelectorAll('.filter-button');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('filter-active'));
                    filterButtons.forEach(btn => btn.classList.add('filter-inactive'));
                    this.classList.remove('filter-inactive');
                    this.classList.add('filter-active');
                });
            });
             
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                });
            });
             
            const beginButton = document.querySelector('.begin-button');
            beginButton.addEventListener('click', function() {
                alert('Starting workout!');
            });
        });
    </script>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.right = '0';
            overlay.style.bottom = '0';
            overlay.style.backgroundColor = 'rgba(15, 15, 26, 0.95)';
            overlay.style.zIndex = '2000';
            overlay.style.padding = '20px';
            overlay.style.overflowY = 'auto';
            
            overlay.innerHTML = `
                <div style="display: flex; justify-content: flex-end;">
                    <button id="close-menu" style="background: none; border: none; color: white; font-size: 1.5rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div style="margin-top: 30px;">
                    <h2 style="font-size: 1.8rem; margin-bottom: 20px;">GYMVERSE</h2>
                    <nav>
                        <div style="margin-bottom: 30px;">
                            <h3 style="font-size: 0.8rem; text-transform: uppercase; color: var(--gray-light); margin-bottom: 15px;">Dashboard</h3>
                            <ul style="list-style: none;">
                                <li style="margin-bottom: 15px;"><a href="profile.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li style="margin-bottom: 15px;"><a href="workout-analytics.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-chart-line"></i> Analytics</a></li>
                                <li style="margin-bottom: 15px;"><a href="current-goal.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-bullseye"></i> Goals</a></li>
                            </ul>
                        </div>
                        <div style="margin-bottom: 30px;">
                            <h3 style="font-size: 0.8rem; text-transform: uppercase; color: var(--gray-light); margin-bottom: 15px;">Training</h3>
                            <ul style="list-style: none;">
                                <li style="margin-bottom: 15px;"><a href="active-workout.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-stopwatch"></i> Quick Workout</a></li>
                                <li style="margin-bottom: 15px;"><a href="../workouts.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-dumbbell"></i> Workouts</a></li>
                                <li style="margin-bottom: 15px;"><a href="calories-burned.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;"><i class="fas fa-fire"></i> Calories Burned</a></li>
                            </ul>
                        </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            
            document.getElementById('close-menu').addEventListener('click', function() {
                document.body.removeChild(overlay);
            });
        });
    }
});
</script> 