<style>
    :root {
        --admin-sidebar-width: 260px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: #0a0a0b;
        margin: 0;
        min-height: 100vh;
        overflow-x: hidden;
        color: white;
        font-family: 'Inter', sans-serif;
    }

    /* Sidebar */
    .sidebar {
        width: var(--admin-sidebar-width);
        background: #111114;
        border-right: 1px solid rgba(255,255,255,0.05);
        display: flex;
        flex-direction: column;
        padding: 30px 20px;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 100;
        box-sizing: border-box;
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 40px;
        text-decoration: none;
        color: white;
    }

    .sidebar-logo .logo-icon {
        width: 32px;
        height: 32px;
        background: var(--accent-color);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #a0a0a0;
        text-decoration: none;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .nav-link:hover, .nav-link.active {
        color: white;
        background: rgba(138, 43, 226, 0.1);
    }

    .nav-link.active {
        color: var(--accent-color);
        background: rgba(138, 43, 226, 0.1);
    }

    /* Main Content */
    .main-content {
        margin-left: var(--admin-sidebar-width);
        padding: 40px;
        min-height: 100vh;
        box-sizing: border-box;
    }

    @media (min-width: 1400px) {
        .main-content { padding: 40px 60px; }
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
    }

    .header-title h1 {
        font-size: 28px;
        margin-bottom: 4px;
    }

    .header-title p {
        color: #666;
        font-size: 14px;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: #16161a;
        padding: 24px;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.03);
        display: flex;
        flex-direction: column;
        gap: 12px;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: rgba(138, 43, 226, 0.2);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-info .stat-value {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .stat-info .stat-label {
        color: #666;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Layout Groups */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    .content-box {
        background: #16161a;
        border-radius: 24px;
        padding: 30px;
        border: 1px solid rgba(255,255,255,0.03);
    }

    .box-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .box-header h3 {
        font-size: 18px;
        font-weight: 700;
    }

    /* Table Style */
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table th {
        text-align: left;
        padding: 12px 16px;
        color: #555;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .admin-table td {
        padding: 16px;
        font-size: 14px;
        border-bottom: 1px solid rgba(255,255,255,0.03);
    }

    .status-pill {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
    .status-approved { background: rgba(56, 239, 125, 0.1); color: #38ef7d; }
    .status-rejected { background: rgba(255, 68, 68, 0.1); color: #ff4444; }

    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        .sidebar {
            width: 80px;
            padding: 30px 10px;
        }
        .sidebar-logo span, .nav-link span {
            display: none;
        }
        .main-content {
            margin-left: 80px;
            padding: 20px;
        }
    }

    /* Inner Alignment Wrapper */
    .admin-container {
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
    }
</style>
