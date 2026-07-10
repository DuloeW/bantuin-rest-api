<style>
    /* Sleek Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(150, 150, 150, 0.2);
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(150, 150, 150, 0.4);
    }

    /* Glassmorphism for Sidebar & Topbar in Dark Mode */
    .dark .fi-sidebar {
        background-color: rgba(15, 15, 17, 0.65) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
    }
    .dark .fi-topbar {
        background-color: rgba(15, 15, 17, 0.65) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    /* Subtle App Background Gradient */
    .dark body {
        background: radial-gradient(circle at top center, #18181b, #09090b) !important;
    }
    
    .dark .fi-layout {
        background: transparent !important;
    }

    /* Soft Glow on Primary Buttons */
    .dark button[type="submit"], .dark button.fi-btn-color-primary {
        box-shadow: 0 0 12px rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
    }
    .dark button[type="submit"]:hover, .dark button.fi-btn-color-primary:hover {
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    /* Sleek Cards */
    .dark .fi-ta-ctn, .dark .fi-wi-stats-overview-stat, .dark .fi-fo-component-ctn {
        background-color: rgba(24, 24, 27, 0.7) !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5) !important;
    }
    
    /* Elegant input fields */
    .dark input, .dark select, .dark textarea {
        background-color: rgba(39, 39, 42, 0.5) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        transition: all 0.3s ease;
    }
    .dark input:focus, .dark select:focus, .dark textarea:focus {
        border-color: rgba(255, 255, 255, 0.25) !important;
        box-shadow: 0 0 10px rgba(255,255,255, 0.05) !important;
    }
</style>
