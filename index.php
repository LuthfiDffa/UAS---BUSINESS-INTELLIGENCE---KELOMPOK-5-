<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
<title>EV Infrastructure Planner — PHP Edition</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{
  --bg:#F8FAFC;--bg2:#F1F5F9;--card:#FFFFFF;--card2:#F8FAFC;
  --border:#E2E8F0;--border2:#CBD5E1;
  --accent:#0891B2;--accent2:#06B6D4;--accent3:#10B981;--accent4:#F59E0B;--accent5:#8B5CF6;
  --text:#0F172A;--text2:#334155;--text3:#64748B;
  --glow:rgba(8,145,178,0.1);
  --font-display:'Poppins',sans-serif;--font-body:'Open Sans',sans-serif;--font-mono:'Share Tech Mono',monospace;
  --nav-h:64px;--sidebar-w:260px;--radius:16px;--trans:0.3s cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:var(--font-body);background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;}
body::before{content:'';position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(8,145,178,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(8,145,178,.04) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;}

/* SIDEBAR */
.sidebar{position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);background:var(--card);border-right:1px solid var(--border);z-index:100;display:flex;flex-direction:column;transition:transform var(--trans);box-shadow:2px 0 24px rgba(15,23,42,.04);}
.sidebar-logo{padding:24px 20px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.logo-icon{width:42px;height:42px;flex-shrink:0;background:linear-gradient(135deg,var(--accent2),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;box-shadow:0 8px 16px rgba(8,145,178,.2);}
.logo-text{font-family:var(--font-display);font-size:14px;font-weight:700;line-height:1.2;color:var(--text);letter-spacing:0.5px;}
.logo-sub{font-size:10px;color:var(--text3);font-family:var(--font-body);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;}
.nav-items{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:6px;overflow-y:auto;}
.nav-item{display:flex;align-items:center;gap:14px;padding:12px 16px;border-radius:12px;cursor:pointer;font-family:var(--font-body);font-weight:600;font-size:14px;color:var(--text3);transition:all var(--trans);position:relative;border:1px solid transparent;}
.nav-item::before{content:'';position:absolute;left:0;top:8px;bottom:8px;width:4px;background:var(--accent);opacity:0;border-radius:0 4px 4px 0;transition:all var(--trans);transform:scaleY(0.5);}
.nav-item:hover{background:var(--bg2);color:var(--text2);}
.nav-item.active{background:rgba(8,145,178,.08);color:var(--accent);border-color:rgba(8,145,178,.15);}
.nav-item.active::before{opacity:1;transform:scaleY(1);}
.nav-icon{font-size:18px;flex-shrink:0;}
.nav-badge{margin-left:auto;font-family:var(--font-body);font-weight:700;font-size:10px;background:var(--bg);color:var(--text3);padding:3px 8px;border-radius:20px;border:1px solid var(--border);}
.nav-item.active .nav-badge{background:var(--accent);color:#fff;border-color:var(--accent);}
.sidebar-footer{padding:16px;border-top:1px solid var(--border);background:var(--card2);}

/* TOPBAR (mobile) */
.topbar{display:none;position:fixed;top:0;left:0;right:0;height:var(--nav-h);background:var(--card);border-bottom:1px solid var(--border);z-index:200;align-items:center;justify-content:space-between;padding:0 16px;box-shadow:0 2px 10px rgba(15,23,42,.05);}
.hamburger{background:none;border:none;cursor:pointer;color:var(--text2);font-size:24px;padding:6px;display:flex;align-items:center;}
.topbar-logo{font-family:var(--font-display);font-size:14px;color:var(--text);font-weight:700;display:flex;align-items:center;gap:8px;}
.drawer-overlay{display:none;position:fixed;inset:0;z-index:300;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);opacity:0;transition:opacity var(--trans);}
.drawer-overlay.open{display:block;opacity:1;}
.sidebar.drawer-open{transform:translateX(0)!important;}

/* MAIN */
.main{margin-left:var(--sidebar-w);min-height:100vh;position:relative;z-index:1;}
.screen{display:none;}
.screen.active{display:block;animation:fadeSlide .4s cubic-bezier(0.16, 1, 0.3, 1);}
@keyframes fadeSlide{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}

/* PAGE HEADER */
.page-header{background:var(--card);border-bottom:1px solid var(--border);padding:32px 32px 24px;position:relative;overflow:hidden;}
.page-header::after{content:'';position:absolute;right:0;top:0;width:300px;height:100%;background:linear-gradient(90deg,transparent,rgba(8,145,178,.04));pointer-events:none;}
.page-title{font-family:var(--font-display);font-size:22px;font-weight:700;color:var(--text);margin-bottom:6px;display:flex;align-items:center;gap:10px;letter-spacing:-0.5px;}
.page-subtitle{font-size:13px;color:var(--text3);font-family:var(--font-body);font-weight:500;}
.header-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.content{padding:32px;}

/* CARDS */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;position:relative;transition:all var(--trans);box-shadow:0 4px 6px -1px rgba(15,23,42,.02),0 2px 4px -2px rgba(15,23,42,.02);}
.card:hover{border-color:var(--border2);box-shadow:0 10px 15px -3px rgba(15,23,42,.04),0 4px 6px -4px rgba(15,23,42,.02);transform:translateY(-2px);}
.card-title{font-family:var(--font-display);font-size:14px;font-weight:600;color:var(--text2);margin-bottom:16px;display:flex;align-items:center;gap:8px;}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:32px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;position:relative;overflow:hidden;transition:all var(--trans);box-shadow:0 4px 6px -1px rgba(15,23,42,.02);}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 12px 20px -3px rgba(15,23,42,.06);border-color:var(--border2);}
.stat-icon{font-size:24px;margin-bottom:12px;display:inline-flex;padding:10px;border-radius:12px;background:var(--bg2);}
.stat-label{font-size:12px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;}
.stat-value{font-family:var(--font-display);font-size:28px;font-weight:700;color:var(--text);line-height:1;}
.stat-value.green{color:var(--accent3);}
.stat-value.orange{color:var(--accent4);}
.stat-value.purple{color:var(--accent5);}
.stat-sub{font-size:12px;color:var(--text3);margin-top:8px;font-weight:500;}

/* CHARTS */
.charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;}
.chart-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;box-shadow:0 4px 6px -1px rgba(15,23,42,.02);}
.chart-card-title{font-family:var(--font-display);font-size:14px;font-weight:600;color:var(--text2);margin-bottom:20px;}
.chart-wrap{position:relative;height:240px;}

/* TABLE */
.data-table-wrap{overflow-x:auto;border-radius:8px;border:1px solid var(--border);background:var(--card);}
.data-table{width:100%;border-collapse:collapse;font-size:14px;font-family:var(--font-body);}
.data-table th{background:var(--bg2);color:var(--text3);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;padding:14px 16px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap;}
.data-table th.sortable{cursor:pointer;transition:color var(--trans);}
.data-table th.sortable:hover{color:var(--text);}
.data-table td{padding:14px 16px;border-bottom:1px solid var(--border);color:var(--text2);white-space:nowrap;}
.data-table tr:hover td{background:var(--bg);}
.data-table tr:last-child td{border-bottom:none;}

/* BADGES */
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;font-family:var(--font-body);}
.badge-bev{background:rgba(8,145,178,.1);color:var(--accent);border:1px solid rgba(8,145,178,.2);}
.badge-phev{background:rgba(139,92,246,.1);color:var(--accent5);border:1px solid rgba(139,92,246,.2);}
.badge-wa{background:var(--bg2);color:var(--text3);border:1px solid var(--border);}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 20px;border-radius:10px;font-family:var(--font-body);font-weight:600;font-size:14px;cursor:pointer;transition:all var(--trans);border:1px solid transparent;}
.btn-primary{background:var(--accent);color:#fff;box-shadow:0 4px 12px rgba(8,145,178,.2);}
.btn-primary:hover{background:var(--accent2);transform:translateY(-1px);box-shadow:0 6px 16px rgba(8,145,178,.3);}
.btn-danger{background:#FEF2F2;color:#EF4444;border-color:#FECACA;}
.btn-danger:hover{background:#FEE2E2;}
.btn-edit{background:#F0F9FF;color:#0EA5E9;border-color:#BAE6FD;}
.btn-edit:hover{background:#E0F2FE;}
.btn-green{background:#F0FDF4;color:#10B981;border-color:#BBF7D0;}
.btn-green:hover{background:#DCFCE7;}
.btn-sm{padding:6px 14px;font-size:13px;border-radius:8px;}

/* FORMS */
.form-group{margin-bottom:20px;}
.form-label{display:block;font-size:13px;font-weight:600;color:var(--text2);margin-bottom:8px;}
.form-input,.form-select{width:100%;padding:12px 16px;background:var(--card);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:var(--font-body);font-size:14px;transition:all var(--trans);box-shadow:0 1px 2px rgba(15,23,42,.02);}
.form-input:focus,.form-select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(8,145,178,.15);}
.form-select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 16px center;background-size:16px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}

/* FILTERS */
.filters-bar{display:flex;gap:16px;flex-wrap:wrap;align-items:center;padding:16px 32px;background:var(--card);border-bottom:1px solid var(--border);}
.filter-select{padding:10px 36px 10px 16px;background:var(--card);border:1px solid var(--border);border-radius:10px;color:var(--text2);font-family:var(--font-body);font-size:14px;cursor:pointer;transition:all var(--trans);box-shadow:0 1px 2px rgba(15,23,42,.02);}
.filter-select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(8,145,178,.15);}
.search-wrap{position:relative;}
.search-wrap::before{content:'';position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E");background-size:contain;pointer-events:none;}
.search-input{padding-left:40px!important;}

/* TABS */
.tab-bar{display:flex;gap:8px;padding:0 32px;border-bottom:1px solid var(--border);background:var(--card);margin-top:-1px;}
.tab-btn{padding:14px 20px;border:none;border-bottom:2px solid transparent;cursor:pointer;font-family:var(--font-body);font-weight:600;font-size:14px;color:var(--text3);background:transparent;transition:all var(--trans);margin-bottom:-1px;}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent);}
.tab-btn:hover:not(.active){color:var(--text2);border-bottom-color:var(--border);}
.tab-content{display:none;}
.tab-content.active{display:block;animation:fadeSlide .3s ease;}

/* PAGINATION */
.pagination{display:flex;align-items:center;gap:6px;padding:16px;border-top:1px solid var(--border);flex-wrap:wrap;background:var(--card);}
.page-btn{padding:8px 14px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text2);cursor:pointer;font-family:var(--font-body);font-weight:500;font-size:13px;transition:all var(--trans);min-width:36px;text-align:center;}
.page-btn:hover:not(:disabled){border-color:var(--accent);color:var(--accent);background:var(--bg);}
.page-btn.active{background:var(--accent);color:#fff;border-color:var(--accent);}
.page-btn:disabled{opacity:.5;cursor:not-allowed;background:var(--bg2);}
.page-info{font-family:var(--font-body);font-size:13px;color:var(--text3);margin-left:12px;font-weight:500;}

/* MODAL */
.modal-overlay{position:fixed;inset:0;z-index:500;background:rgba(15,23,42,.4);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity var(--trans);}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(15,23,42,.15);transform:scale(.95);transition:transform var(--trans);}
.modal-overlay.open .modal{transform:scale(1);}
.modal-title{font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--text);margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
.modal-footer{display:flex;gap:12px;justify-content:flex-end;margin-top:28px;padding-top:20px;border-top:1px solid var(--border);}

/* MAP */
#map{height:540px;border-radius:var(--radius);border:1px solid var(--border);box-shadow:0 4px 6px -1px rgba(15,23,42,.02);}
.leaflet-container{background:#E2E8F0!important;font-family:var(--font-body);}

/* TOAST */
.toast-container{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;}
.toast{padding:14px 20px;border-radius:12px;font-family:var(--font-body);font-weight:600;font-size:14px;background:#fff;border:1px solid var(--border);animation:toastIn .3s cubic-bezier(0.16, 1, 0.3, 1);box-shadow:0 10px 15px -3px rgba(15,23,42,.08);display:flex;align-items:center;gap:10px;min-width:280px;}
.toast-success{border-left:4px solid var(--accent3);}
.toast-error{border-left:4px solid var(--accent4);}
.toast-info{border-left:4px solid var(--accent);}
@keyframes toastIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}

/* CITY INFO BOX */
.city-info-box{margin-top:12px;background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:16px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
.city-info-field label{font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:4px;}
.city-info-field span{font-size:14px;color:var(--text);font-weight:500;}
.city-info-field.wide{grid-column:span 2;}

/* EMPTY STATE */
.empty-state{text-align:center;padding:80px 20px;color:var(--text3);}
.empty-state .empty-icon{font-size:48px;margin-bottom:20px;opacity:0.8;}
.empty-state h3{font-family:var(--font-display);font-size:16px;font-weight:600;color:var(--text2);margin-bottom:8px;}
.empty-state p{font-size:14px;}

/* LOADING */
.loading-row td{text-align:center;padding:60px;color:var(--text3);}
.spinner{display:inline-block;width:24px;height:24px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .8s linear infinite;margin-right:10px;vertical-align:middle;}
@keyframes spin{to{transform:rotate(360deg);}}

/* LOCATION CARD GRID */
.loc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;}
.loc-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;transition:all var(--trans);box-shadow:0 2px 4px -2px rgba(15,23,42,.02);}
.loc-card:hover{border-color:var(--border2);transform:translateY(-3px);box-shadow:0 10px 15px -3px rgba(15,23,42,.05);}
.loc-card-city{font-family:var(--font-display);font-size:16px;color:var(--text);font-weight:700;margin-bottom:8px;}
.loc-card-meta{font-size:13px;color:var(--text3);margin-bottom:16px;line-height:1.6;}
.loc-card-coords{font-size:12px;color:var(--text3);font-family:var(--font-mono);margin-bottom:20px;background:var(--bg2);padding:6px 10px;border-radius:6px;display:inline-block;}
.loc-card-actions{display:flex;gap:10px;}

/* DSS */
.dss-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;}
.dss-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;transition:all var(--trans);position:relative;overflow:hidden;box-shadow:0 4px 6px -1px rgba(15,23,42,.02);}
.dss-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--border);transition:background var(--trans);}
.dss-card:hover{border-color:var(--border2);transform:translateY(-3px);box-shadow:0 12px 20px -3px rgba(15,23,42,.06);}
.dss-card:hover::before{background:var(--accent);}
.dss-city{font-family:var(--font-display);font-size:18px;color:var(--text);font-weight:700;margin-bottom:6px;}
.dss-county{font-size:13px;color:var(--text3);margin-bottom:20px;}
.dss-score-bar{height:8px;background:var(--bg2);border-radius:4px;margin-top:12px;overflow:hidden;}
.dss-score-fill{height:100%;border-radius:4px;transition:width 1s cubic-bezier(0.16, 1, 0.3, 1);}
.dss-score-high{background:var(--accent3);}
.dss-score-mid{background:var(--accent4);}
.dss-score-low{background:#EF4444;}
.dss-stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:20px;}
.dss-stat-item{background:var(--bg2);border-radius:10px;padding:12px 16px;border:1px solid var(--border);}
.dss-stat-label{font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;}
.dss-stat-val{font-family:var(--font-display);font-size:18px;color:var(--text);font-weight:700;}
.dss-rank{position:absolute;top:20px;right:20px;font-family:var(--font-display);font-size:14px;font-weight:700;color:var(--text3);background:var(--bg);border:1px solid var(--border);padding:4px 10px;border-radius:8px;}
.methodology-card{background:#F0F9FF;border:1px solid #BAE6FD;border-radius:var(--radius);padding:24px;margin-bottom:28px;}
.method-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-top:16px;}
.method-item{background:#fff;border-radius:12px;padding:16px;border:1px solid #E0F2FE;box-shadow:0 2px 4px rgba(14,165,233,.05);}
.method-label{font-size:12px;color:var(--accent);font-weight:600;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.method-val{font-size:14px;color:var(--text2);line-height:1.6;}
.dss-summary-row{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap;}
.dss-summary-pill{display:flex;align-items:center;gap:10px;padding:12px 20px;border-radius:12px;font-size:14px;font-weight:600;background:var(--card);border:1px solid var(--border);box-shadow:0 2px 4px -2px rgba(15,23,42,.02);}
.pill-tinggi{color:var(--accent3);border-left:4px solid var(--accent3);}
.pill-sedang{color:var(--accent4);border-left:4px solid var(--accent4);}
.pill-rendah{color:#EF4444;border-left:4px solid #EF4444;}
.badge-high{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;}
.badge-mid{background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;}
.badge-low{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;}
.dss-score-breakdown{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;}
.score-chip{font-family:var(--font-mono);font-size:11px;padding:4px 10px;border-radius:6px;background:var(--bg2);border:1px solid var(--border);color:var(--text2);font-weight:500;}

/* RESPONSIVE DSS */
@media(max-width:1024px){.dss-grid{grid-template-columns:1fr;}}

@media(max-width:900px){
  .sidebar{transform:translateX(-100%);}
  .topbar{display:flex;}
  .main{margin-left:0;padding-top:var(--nav-h);}
  .charts-grid{grid-template-columns:1fr;}
  .form-row{grid-template-columns:1fr;}
  .city-info-box{grid-template-columns:1fr 1fr;}
  .content{padding:20px;}
  .page-header{padding:24px 20px;}
  .filters-bar{padding:16px 20px;}
  .tab-bar{padding:0 20px;}
}
@media(max-width:600px){
  .stats-grid{grid-template-columns:1fr;}
  .loc-grid{grid-template-columns:1fr;}
  .city-info-box{grid-template-columns:1fr;}
  .city-info-field.wide{grid-column:span 1;}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">⚡</div>
    <div>
      <div class="logo-text">EV PUMP PLANNER</div>
    </div>
  </div>
  <div class="nav-items">
    <div class="nav-item active" data-screen="dashboard"><span class="nav-icon">📊</span> Dashboard</div>
    <div class="nav-item" data-screen="map"><span class="nav-icon">🗺️</span> Washington Map</div>
    <div class="nav-item" data-screen="dss"><span class="nav-icon">🧠</span> Decision Support</div>
    <div class="nav-item" data-screen="crud-locations"><span class="nav-icon">📍</span> Kelola Lokasi</div>
    <div class="nav-item" data-screen="rencana-pom"><span class="nav-icon">⚡</span> Lokasi Rencana Pom</div>
  </div>
  <div class="sidebar-footer">
    <div style="font-family:var(--font-mono);font-size:10px;color:var(--text3);text-align:center;padding:4px;">
    </div>
  </div>
</nav>

<div class="topbar" id="topbar">
  <button class="hamburger" id="hamburger">☰</button>
  <div class="topbar-logo">⚡ EV PLANNER</div>
</div>
<div class="drawer-overlay" id="drawerOverlay"></div>

<main class="main">

<!-- ═══════════════════ DASHBOARD ═══════════════════ -->
<div class="screen active" id="screen-dashboard">
  <div class="page-header">
    <div class="header-row">
      <div>
        <div class="page-title">⚡ DASHBOARD</div>
      </div>
      <button class="btn btn-primary btn-sm" onclick="loadDashboard()">🔄 Refresh</button>
    </div>
  </div>
  <div class="content">
    <div class="stats-grid" id="statsGrid">
      <div class="stat-card"><div class="spinner"></div> Loading...</div>
    </div>
    <div class="charts-grid">
      <div class="chart-card"><div class="chart-card-title">EV per Tahun Model</div><div class="chart-wrap"><canvas id="chartYear"></canvas></div></div>
      <div class="chart-card"><div class="chart-card-title">EV per Merek</div><div class="chart-wrap"><canvas id="chartMake"></canvas></div></div>
      <div class="chart-card"><div class="chart-card-title">Distribusi BEV vs PHEV</div><div class="chart-wrap"><canvas id="chartType"></canvas></div></div>
      <div class="chart-card"><div class="chart-card-title">Top County</div><div class="chart-wrap"><canvas id="chartCounty"></canvas></div></div>
    </div>
    <div class="card">
      <div class="card-glow"></div>
      <div class="card-title">🏆 Top 5 Kota — Unit EV Terdaftar</div>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead><tr><th>#</th><th>Kota</th><th>County</th><th>Total Unit</th></tr></thead>
          <tbody id="topCitiesTbody"><tr class="loading-row"><td colspan="4"><span class="spinner"></span>Loading...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════ MAP ═══════════════════ -->
<div class="screen" id="screen-map">
  <div class="page-header">
    <div class="header-row">
      <div>
        <div class="page-title">🗺️ Washington State Map</div>
        <div class="page-subtitle">Titik Populasi Kendaraan Listrik dan Lokasi Rencana POM</div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:8px 14px;">
        <span style="font-size:16px;">💡</span>
        <span style="font-size:12px;color:#059669;font-weight:600;font-family:var(--font-body);">Klik lokasi di peta untuk menambah rencana POM</span>
      </div>
    </div>
  </div>
  <div class="content">
    <div id="map"></div>
    <div style="margin-top:16px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;display:flex;gap:20px;flex-wrap:wrap;align-items:center;">
      <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);font-weight:500;"><span style="width:12px;height:12px;border-radius:50%;background:#0891B2;display:inline-block;"></span>BEV</div>
      <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);font-weight:500;"><span style="width:12px;height:12px;border-radius:50%;background:#8B5CF6;display:inline-block;"></span>PHEV</div>
      <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);font-weight:500;"><span style="width:12px;height:12px;border-radius:50%;background:#10B981;display:inline-block;"></span>Lokasi (tanpa data EV)</div>
      <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);font-weight:500;"><span style="width:14px;height:14px;border-radius:50%;background:#F59E0B;display:inline-block;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.1);"></span>Rencana POM</div>
    </div>
  </div>
</div>

<!-- ═══════════════════ CRUD LOCATIONS ═══════════════════ -->
<div class="screen" id="screen-crud-locations">
  <div class="page-header">
    <div class="header-row">
      <div>
        <div class="page-title">📍 KELOLA LOKASI</div>
        <div class="page-subtitle">Manajemen Titik Populasi EV Washington State</div>
      </div>
      <button class="btn btn-primary" onclick="openAddLocationModal()">➕ Tambah Kota</button>
    </div>
  </div>
  <div class="tab-bar">
    <button class="tab-btn active" onclick="switchLocTab('table',this)">📋 Tabel</button>
    <button class="tab-btn" onclick="switchLocTab('grid',this)">🃏 Grid Kartu</button>
  </div>

  <!-- Filters -->
  <div class="filters-bar">
    <div class="search-wrap" style="flex:1;min-width:200px;">
      <input type="text" class="form-input search-input" id="locSearch" placeholder="Cari kota / kode pos..." oninput="debounceLoc()">
    </div>
    <select class="filter-select" id="locCountyFilter" onchange="loadLocations()">
      <option value="">Semua County</option>
    </select>
    <button class="btn btn-sm" style="background:rgba(8,145,178,.08);color:var(--text2);border:1px solid var(--border);" onclick="resetLocFilters()">✕ Reset</button>
  </div>

  <!-- Table View -->
  <div class="tab-content active" id="locTab-table">
    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th class="sortable" onclick="sortLoc('city')">Kota ↕</th>
            <th class="sortable" onclick="sortLoc('county')">County ↕</th>
            <th>State</th>
            <th>Kode Pos</th>
            <th>Koordinat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="locTbody">
          <tr class="loading-row"><td colspan="7"><span class="spinner"></span>Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
    <div class="pagination" id="locPagination"></div>
  </div>

  <!-- Grid View -->
  <div class="tab-content" id="locTab-grid">
    <div class="content">
      <div class="loc-grid" id="locGrid">
        <div style="padding:40px;color:var(--text3);font-family:var(--font-mono);">Loading...</div>
      </div>
      <div class="pagination" id="locPaginationGrid"></div>
    </div>
  </div>
</div>

<!-- ═══════════════════ DSS / SPK ═══════════════════ -->
<div class="screen" id="screen-dss">
  <div class="page-header">
    <div class="header-row">
      <div>
        <div class="page-title">🧠 SISTEM PENDUKUNG KEPUTUSAN</div>
        <div class="page-subtitle">Rekomendasi Prioritas Pembangunan Lokasi Pom Listrik</div>
      </div>
      <button class="btn btn-primary btn-sm" onclick="loadDSS()">🔄 Refresh</button>
    </div>
  </div>

  <!-- Filters Bar -->
  <div class="filters-bar">
    <div style="display:flex;align-items:center;gap:8px;">
      <span style="font-size:11px;font-family:var(--font-mono);color:var(--text3);text-transform:uppercase;letter-spacing:1px;">Prioritas:</span>
      <select class="filter-select" id="dssLevelFilter" onchange="loadDSS()">
        <option value="">Semua Level</option>
        <option value="Tinggi">Tinggi</option>
        <option value="Sedang">Sedang</option>
        <option value="Rendah">Rendah</option>
      </select>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <span style="font-size:11px;font-family:var(--font-mono);color:var(--text3);text-transform:uppercase;letter-spacing:1px;">Urutkan:</span>
      <select class="filter-select" id="dssSortFilter" onchange="loadDSS()">
        <option value="score">Skor Prioritas</option>
        <option value="units">Jumlah EV</option>
        <option value="county">County</option>
      </select>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <span style="font-size:11px;font-family:var(--font-mono);color:var(--text3);text-transform:uppercase;letter-spacing:1px;">Tipe EV:</span>
      <select class="filter-select" id="dssEvTypeFilter" onchange="loadDSS()">
        <option value="">BEV + PHEV</option>
        <option value="BEV">BEV Only</option>
        <option value="PHEV">PHEV Only</option>
      </select>
    </div>
    <div style="margin-left:auto;display:flex;align-items:center;gap:6px;">
      <span class="spinner" id="dssSpinner" style="display:none;"></span>
      <span id="dssCountLabel" style="font-family:var(--font-mono);font-size:12px;color:var(--text3);">— hasil</span>
    </div>
  </div>

  <div class="content">
    <!-- Metodologi -->
    <div class="methodology-card">
      <div class="card-title" style="color:var(--accent);font-size:11px;margin-bottom:0;">ℹ️ METODOLOGI SPK — WEIGHTED SCORING MODEL</div>
      <div class="method-grid">
        <div class="method-item">
          <div class="method-label">🎯 Kriteria &amp; Bobot</div>
          <div class="method-val">Densitas EV <strong style="color:var(--accent);">50%</strong> + Jangkauan Rata-rata <strong style="color:var(--accent);">25%</strong> + Tipe EV (BEV Ratio) <strong style="color:var(--accent);">25%</strong></div>
        </div>
        <div class="method-item">
          <div class="method-label">📊 Skala Prioritas</div>
          <div class="method-val">
            <span style="color:var(--accent3);">■</span> Tinggi ≥60 pts &nbsp;
            <span style="color:#ffd500;">■</span> Sedang ≥25 pts &nbsp;
            <span style="color:var(--accent4);">■</span> Rendah &lt;25 pts
          </div>
        </div>
        <div class="method-item">
          <div class="method-label">⚙️ Algoritma</div>
          <div class="method-val">Normalisasi Min-Max + Weighted Sum. Data real-time dari database MySQL.</div>
        </div>
        <div class="method-item">
          <div class="method-label">🗄️ Sumber Data</div>
          <div class="method-val">Tabel <code style="color:var(--accent3);">ev_facts</code> JOIN <code style="color:var(--accent3);">locations</code> + <code style="color:var(--accent3);">vehicles</code></div>
        </div>
      </div>
    </div>

    <!-- Summary Pills -->
    <div class="dss-summary-row" id="dssSummaryRow">
      <div class="dss-summary-pill pill-tinggi"><span>⬆</span> Tinggi: <strong id="pillTinggi">—</strong></div>
      <div class="dss-summary-pill pill-sedang"><span>➡</span> Sedang: <strong id="pillSedang">—</strong></div>
      <div class="dss-summary-pill pill-rendah"><span>⬇</span> Rendah: <strong id="pillRendah">—</strong></div>
    </div>

    <!-- DSS Cards Grid -->
    <div class="dss-grid" id="dssGrid">
      <div style="grid-column:span 2;text-align:center;padding:60px 20px;color:var(--text3);font-family:var(--font-mono);">
        <span class="spinner"></span> Memuat data DSS dari database...
      </div>
    </div>

    <!-- Chart Perbandingan Skor -->
    <div class="chart-card" style="margin-top:22px;">
      <div class="chart-card-title">Perbandingan Skor Prioritas SPKLU per Kota</div>
      <div class="chart-wrap" style="height:280px;"><canvas id="chartDss"></canvas></div>
    </div>

    <!-- Detail Breakdown Table -->
    <div class="card" style="margin-top:22px;">
      <div class="card-glow"></div>
      <div class="card-title">📋 TABEL DETAIL SKOR DSS</div>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Kota</th>
              <th>County</th>
              <th>Total EV</th>
              <th>Avg Range</th>
              <th>BEV%</th>
              <th>Skor Densitas</th>
              <th>Skor Range</th>
              <th>Skor Tipe</th>
              <th>Total Skor</th>
              <th>Level</th>
            </tr>
          </thead>
          <tbody id="dssTableBody">
            <tr class="loading-row"><td colspan="11"><span class="spinner"></span>Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<!-- ═══════════════════ LOKASI RENCANA POM ═══════════════════ -->
<div class="screen" id="screen-rencana-pom">
  <div class="page-header">
    <div class="header-row">
      <div>
        <div class="page-title">⚡ LOKASI RENCANA POM</div>
        <div class="page-subtitle">Daftar Rencana Pembangunan Stasiun Pengisian Kendaraan Listrik Umum</div>
      </div>
      <button class="btn btn-primary" onclick="openAddRencanaModal()">&#xFF0B; Tambah Rencana</button>
    </div>
  </div>

  <!-- Filters -->
  <div class="filters-bar">
    <div class="search-wrap" style="flex:1;min-width:200px;">
      <input type="text" class="form-input search-input" id="pomSearch" placeholder="Cari nama lokasi / kota..." oninput="debouncePom()">
    </div>
    <select class="filter-select" id="pomStatusFilter" onchange="loadRencanaPom()">
      <option value="">Semua Status</option>
      <option value="Direncanakan">Direncanakan</option>
      <option value="Dalam Proses">Dalam Proses</option>
      <option value="Selesai">Selesai</option>
    </select>
    <button class="btn btn-sm" style="background:rgba(8,145,178,.08);color:var(--text2);border:1px solid var(--border);" onclick="resetPomFilters()">✕ Reset</button>
  </div>

  <!-- Summary Pills -->
  <div style="display:flex;gap:12px;padding:16px 20px 0;flex-wrap:wrap;" id="pomSummaryRow">
    <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;background:#F0F9FF;border:1px solid #BAE6FD;font-weight:600;font-family:var(--font-body);font-size:13px;color:#0369A1;">📌 Direncanakan: <strong id="pomPillRencana">—</strong></div>
    <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;background:#FFFBEB;border:1px solid #FDE68A;font-weight:600;font-family:var(--font-body);font-size:13px;color:#B45309;">🔧 Dalam Proses: <strong id="pomPillProses">—</strong></div>
    <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);font-family:var(--font-mono);font-size:12px;color:var(--accent3);">&#x2705; Selesai: <strong id="pomPillSelesai">—</strong></div>
  </div>

  <!-- Table -->
  <div class="content" style="padding-top:16px;">
    <div class="card" style="padding:0;overflow:hidden;">
      <div class="card-glow"></div>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nama Lokasi</th>
              <th>Kota / Kecamatan</th>
              <th>Koordinat</th>
              <th>Tipe</th>
              <th>Kapasitas</th>
              <th>Slot</th>
              <th>Target</th>
              <th>Est. Biaya</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="pomTbody">
            <tr class="loading-row"><td colspan="11"><span class="spinner"></span>Memuat data...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="pagination" id="pomPagination"></div>
    </div>
  </div>
</div>

</main><!-- /main -->
<!-- ════════════════ MODAL LOKASI ════════════════ -->
<div class="modal-overlay" id="locModal">
  <div class="modal">
    <div class="modal-title" id="locModalTitle">➕ TAMBAH KOTA BARU</div>
    <div id="locModalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-danger" onclick="closeModal('locModal')">✕ Batal</button>
      <button class="btn btn-primary" onclick="saveLocation()">💾 Simpan</button>
    </div>
  </div>
</div>

<!-- ════════════════ MODAL KONFIRMASI HAPUS ════════════════ -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-title">⚠️ KONFIRMASI HAPUS</div>
    <div id="deleteMsg" style="color:var(--text2);font-size:14px;line-height:1.7;"></div>
    <div class="modal-footer">
      <button class="btn btn-edit" onclick="closeModal('deleteModal')">✕ Batal</button>
      <button class="btn btn-danger" id="confirmDeleteBtn">🗑️ Hapus</button>
    </div>
  </div>
</div>

<!-- ════════════════ MODAL RENCANA POM ════════════════ -->
<div class="modal-overlay" id="pomModal">
  <div class="modal" style="max-width:640px;">
    <div class="modal-title" id="pomModalTitle">⚡ TAMBAH RENCANA POM LISTRIK</div>
    <div id="pomModalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-danger" onclick="closeModal('pomModal')">✕ Batal</button>
      <button class="btn btn-primary" onclick="saveRencanaPom()">💾 Simpan Rencana</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ═══════════════════════════════════════════════════════════════
// GLOBAL STATE
// ═══════════════════════════════════════════════════════════════
const API = {
  locations:   'api/locations.php',
  evFacts:     'api/ev_facts.php',
  vehicles:    'api/vehicles.php',
  stats:       'api/stats.php',
  dss:         'api/dss.php',
  rencanaPom:  'api/rencana_pom.php',
};

let locState  = {page:1,search:'',county:'',sort:'city',dir:'ASC',view:'table'};
let editingLoc = null;
let mapInstance = null, charts = {};

// Cache lokasi untuk dropdown
let cacheLocations = [];

// ═══════════════════════════════════════════════════════════════
// NAVIGATION
// ═══════════════════════════════════════════════════════════════
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
    document.querySelectorAll('.screen').forEach(s=>s.classList.remove('active'));
    item.classList.add('active');
    const screen = item.dataset.screen;
    document.getElementById('screen-'+screen)?.classList.add('active');
    closeDrawer();
    // Lazy load on first visit
    if(screen==='dashboard')    loadDashboard();
    if(screen==='map')          loadMap();
    if(screen==='dss')          loadDSS();
    if(screen==='crud-locations') loadLocations();
    if(screen==='rencana-pom')    loadRencanaPom();
  });
});
document.getElementById('hamburger').addEventListener('click', ()=>{
  document.getElementById('sidebar').classList.add('drawer-open');
  document.getElementById('drawerOverlay').classList.add('open');
});
document.getElementById('drawerOverlay').addEventListener('click', closeDrawer);
function closeDrawer(){
  document.getElementById('sidebar').classList.remove('drawer-open');
  document.getElementById('drawerOverlay').classList.remove('open');
}

// ═══════════════════════════════════════════════════════════════
// UTILITY
// ═══════════════════════════════════════════════════════════════
function toast(msg, type='info'){
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.textContent = msg;
  document.getElementById('toastContainer').prepend(el);
  setTimeout(()=>el.remove(), 4000);
}

function fmt(n){ return Number(n||0).toLocaleString('id-ID'); }

async function api(endpoint, method='GET', body=null){
  const opts = { method, headers: {'Content-Type':'application/json'} };
  if(body) opts.body = JSON.stringify(body);
  const res = await fetch(endpoint, opts);
  return res.json();
}

function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); editingLoc=null; }

function buildPagination(containerId, pagination, onPage){
  const el = document.getElementById(containerId);
  if(!el) return;
  const {total,page,pages,per_page} = pagination;
  let html = `<button class="page-btn" ${page<=1?'disabled':''} onclick="${onPage}(${page-1})">‹</button>`;
  const start = Math.max(1, page-2), end = Math.min(pages, page+2);
  if(start>1) html += `<button class="page-btn" onclick="${onPage}(1)">1</button>${start>2?'<span style="color:var(--text3);padding:0 4px;">…</span>':''}`;
  for(let i=start;i<=end;i++) html += `<button class="page-btn${i===page?' active':''}" onclick="${onPage}(${i})">${i}</button>`;
  if(end<pages) html += `${end<pages-1?'<span style="color:var(--text3);padding:0 4px;">…</span>':''}<button class="page-btn" onclick="${onPage}(${pages})">${pages}</button>`;
  html += `<button class="page-btn" ${page>=pages?'disabled':''} onclick="${onPage}(${page+1})">›</button>`;
  html += `<span class="page-info">${fmt((page-1)*per_page+1)}–${fmt(Math.min(page*per_page,total))} dari ${fmt(total)}</span>`;
  el.innerHTML = html;
}

// ═══════════════════════════════════════════════════════════════
// DSS — SISTEM PENDUKUNG KEPUTUSAN
// ═══════════════════════════════════════════════════════════════
let dssChart = null;

function getPriorityColor(score) {
  return score >= 60 ? '#10B981' : score >= 25 ? '#F59E0B' : '#EF4444';
}

function levelBadge(level) {
  const cls = level === 'Tinggi' ? 'badge-high' : level === 'Sedang' ? 'badge-mid' : 'badge-low';
  const icon = level === 'Tinggi' ? '⬆' : level === 'Sedang' ? '➡' : '⬇';
  return `<span class="badge ${cls}">${icon} ${level.toUpperCase()}</span>`;
}

function fillClass(level) {
  return level === 'Tinggi' ? 'dss-score-high' : level === 'Sedang' ? 'dss-score-mid' : 'dss-score-low';
}

async function loadDSS() {
  const level  = document.getElementById('dssLevelFilter')?.value  || '';
  const sort   = document.getElementById('dssSortFilter')?.value   || 'score';
  const evType = document.getElementById('dssEvTypeFilter')?.value || '';

  // Show spinner
  const spinner = document.getElementById('dssSpinner');
  if (spinner) spinner.style.display = 'inline-block';

  const params = new URLSearchParams({ level, sort, ev_type: evType });
  const res = await api(API.dss + '?' + params);

  if (spinner) spinner.style.display = 'none';
  if (!res.success) { toast('Gagal memuat data DSS', 'error'); return; }

  const data = res.data;
  const meta = res.meta;

  // Update count label
  const countLabel = document.getElementById('dssCountLabel');
  if (countLabel) countLabel.textContent = `${meta.total} kota`;

  // Update summary pills
  document.getElementById('pillTinggi').textContent = meta.count_tinggi;
  document.getElementById('pillSedang').textContent = meta.count_sedang;
  document.getElementById('pillRendah').textContent = meta.count_rendah;

  // ── Render Cards ───────────────────────────────────────────
  const grid = document.getElementById('dssGrid');
  if (!data.length) {
    grid.innerHTML = `<div style="grid-column:span 2;text-align:center;padding:60px 20px;color:var(--text3);font-family:var(--font-mono);">
      <div style="font-size:40px;margin-bottom:16px;">🔍</div>
      <div>Tidak ada data untuk filter ini.</div>
    </div>`;
    document.getElementById('dssTableBody').innerHTML = `<tr><td colspan="11" style="text-align:center;color:var(--text3);padding:32px;font-family:var(--font-mono);">Tidak ada data.</td></tr>`;
    return;
  }

  grid.innerHTML = data.map((d, i) => `
    <div class="dss-card">
      <div class="dss-rank">#${i + 1}</div>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
        <div class="dss-city">${d.city}</div>
        ${levelBadge(d.level)}
      </div>
      <div class="dss-county">📍 ${d.county} County · Washington State</div>

      <div style="display:flex;justify-content:space-between;margin-bottom:5px;align-items:center;">
        <span style="font-size:12px;color:var(--text3);font-family:var(--font-mono);">Skor Prioritas SPKLU</span>
        <span style="font-family:var(--font-display);font-size:14px;color:${getPriorityColor(d.score)};">${d.score}</span>
      </div>
      <div class="dss-score-bar">
        <div class="dss-score-fill ${fillClass(d.level)}" style="width:${d.score}%;"></div>
      </div>

      <div class="dss-score-breakdown">
        <span class="score-chip">Densitas: ${d.density_score}</span>
        <span class="score-chip">Range: ${d.range_score}</span>
        <span class="score-chip">Tipe EV: ${d.type_score}</span>
      </div>

      <div class="dss-stats">
        <div class="dss-stat-item">
          <div class="dss-stat-label">Total EV</div>
          <div class="dss-stat-val">${fmt(d.total_units)}</div>
        </div>
        <div class="dss-stat-item">
          <div class="dss-stat-label">Avg Range</div>
          <div class="dss-stat-val">${d.avg_range} mi</div>
        </div>
        <div class="dss-stat-item">
          <div class="dss-stat-label">BEV</div>
          <div class="dss-stat-val" style="color:var(--accent);">${fmt(d.bev_units)}</div>
        </div>
        <div class="dss-stat-item">
          <div class="dss-stat-label">PHEV</div>
          <div class="dss-stat-val" style="color:var(--accent5);">${fmt(d.phev_units)}</div>
        </div>
        <div class="dss-stat-item" style="grid-column:span 2;">
          <div class="dss-stat-label">Merek Dominan</div>
          <div style="font-size:13px;color:var(--text);font-weight:600;">🏆 ${d.dominant_make}</div>
        </div>
      </div>
    </div>
  `).join('');

  // ── Render Table ───────────────────────────────────────────
  document.getElementById('dssTableBody').innerHTML = data.map((d, i) => `
    <tr>
      <td><span style="font-family:var(--font-mono);color:var(--accent);">#${i+1}</span></td>
      <td style="font-weight:600;color:var(--text);">${d.city}</td>
      <td style="color:var(--text3);">${d.county}</td>
      <td><strong style="color:var(--accent3);font-family:var(--font-mono);">${fmt(d.total_units)}</strong></td>
      <td><span style="font-family:var(--font-mono);">${d.avg_range} mi</span></td>
      <td>
        <div style="display:flex;align-items:center;gap:6px;">
          <div style="width:40px;height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
            <div style="width:${d.bev_ratio}%;height:100%;background:var(--accent);border-radius:2px;"></div>
          </div>
          <span style="font-family:var(--font-mono);font-size:11px;color:var(--text3);">${d.bev_ratio}%</span>
        </div>
      </td>
      <td><span style="font-family:var(--font-mono);color:var(--accent2);">${d.density_score}</span></td>
      <td><span style="font-family:var(--font-mono);color:var(--accent5);">${d.range_score}</span></td>
      <td><span style="font-family:var(--font-mono);color:var(--accent3);">${d.type_score}</span></td>
      <td>
        <strong style="font-family:var(--font-display);font-size:15px;color:${getPriorityColor(d.score)};">${d.score}</strong>
      </td>
      <td>${levelBadge(d.level)}</td>
    </tr>
  `).join('');

  // ── Render Chart ───────────────────────────────────────────
  if (dssChart) dssChart.destroy();
  const ctxDss = document.getElementById('chartDss').getContext('2d');
  dssChart = new Chart(ctxDss, {
    type: 'bar',
    data: {
      labels: data.map(d => d.city),
      datasets: [
        {
          label: 'Skor Densitas (50%)',
          data: data.map(d => d.density_score),
          backgroundColor: 'rgba(8,145,178,0.7)',
          borderColor: '#0891B2',
          borderWidth: 1,
          borderRadius: 4,
        },
        {
          label: 'Skor Range (25%)',
          data: data.map(d => d.range_score),
          backgroundColor: 'rgba(139,92,246,0.7)',
          borderColor: '#8B5CF6',
          borderWidth: 1,
          borderRadius: 4,
        },
        {
          label: 'Skor Tipe EV (25%)',
          data: data.map(d => d.type_score),
          backgroundColor: 'rgba(16,185,129,0.7)',
          borderColor: '#10B981',
          borderWidth: 1,
          borderRadius: 4,
        },
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { color: '#334155', font: { family: "'Open Sans'", size: 12, weight: 500 } } },
        tooltip: {
          backgroundColor: '#FFFFFF', borderColor: '#E2E8F0', borderWidth: 1,
          titleColor: '#0F172A', bodyColor: '#334155',
          padding: 12, boxPadding: 6,
          callbacks: {
            afterBody: (items) => {
              const i = items[0].dataIndex;
              return [`Total Skor: ${data[i].score}`, `Level: ${data[i].level}`];
            }
          }
        }
      },
      scales: {
        x: {
          stacked: true,
          ticks: { color: '#64748B', font: { family: "'Open Sans'", size: 11 }, maxRotation: 45 },
          grid: { color: 'rgba(226,232,240,0.6)' }
        },
        y: {
          stacked: true, max: 100,
          ticks: { color: '#64748B', font: { family: "'Open Sans'", size: 11 }, callback: v => v + ' pts' },
          grid: { color: 'rgba(226,232,240,0.6)' }
        }
      }
    }
  });
}

// ═══════════════════════════════════════════════════════════════
// DASHBOARD
// ═══════════════════════════════════════════════════════════════
let dashLoaded = false;
async function loadDashboard(){
  dashLoaded = true;
  const res = await api(API.stats);
  if(!res.success) return toast('Gagal memuat statistik','error');
  const d = res.data;

  // Stats cards
  document.getElementById('statsGrid').innerHTML = `
    <div class="stat-card"><div class="stat-icon">⚡</div><div class="stat-label">Total Unit EV</div><div class="stat-value">${fmt(d.total_units)}</div><div class="stat-sub">Unit terdaftar</div></div>
    <div class="stat-card"><div class="stat-icon">📍</div><div class="stat-label">Kota Terdaftar</div><div class="stat-value green">${fmt(d.total_cities)}</div><div class="stat-sub">Washington State</div></div>
    <div class="stat-card"><div class="stat-icon">🚗</div><div class="stat-label">Merek/Model</div><div class="stat-value orange">${fmt(d.total_vehicles)}</div><div class="stat-sub">Kendaraan EV</div></div>
    <div class="stat-card"><div class="stat-icon">🔋</div><div class="stat-label">Rata-rata Jangkauan</div><div class="stat-value purple">${d.avg_range} mi</div><div class="stat-sub">Jarak Tempuh</div></div>
  `;

  // Top cities
  document.getElementById('topCitiesTbody').innerHTML = d.top_cities.map((c,i)=>`
    <tr><td><span style="font-family:var(--font-mono);color:var(--text3);">${i+1}</span></td>
    <td style="color:var(--text);font-weight:600;">${c.city}</td>
    <td><span style="color:var(--text3);">${c.county}</span></td>
    <td><span style="font-family:var(--font-mono);color:var(--accent);">${fmt(c.total)}</span></td></tr>
  `).join('');

  // Charts
  const chartDef = (ctx,type,labels,data,colors)=>{
    if(charts[ctx]) charts[ctx].destroy();
    charts[ctx] = new Chart(document.getElementById(ctx).getContext('2d'),{
      type, data:{labels,datasets:[{label:'Total Unit',data,backgroundColor:colors,borderColor:colors.map(c=>c.substring(0,7)),borderWidth:1,borderRadius:4}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{titleColor:'#0F172A',bodyColor:'#334155',backgroundColor:'#FFFFFF',borderColor:'#E2E8F0',borderWidth:1,padding:10}},scales:{x:{ticks:{color:'#64748B',font:{family:"'Open Sans'",size:11}},grid:{color:'rgba(226,232,240,0.6)'}},y:{ticks:{color:'#64748B',font:{family:"'Open Sans'",size:11}},grid:{color:'rgba(226,232,240,0.6)'}}}}
    });
  };
  const pieOpts = (ctx,labels,data,colors)=>{
    if(charts[ctx]) charts[ctx].destroy();
    charts[ctx] = new Chart(document.getElementById(ctx).getContext('2d'),{
      type:'doughnut',data:{labels,datasets:[{data,backgroundColor:colors,borderColor:'#FFFFFF',borderWidth:2}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:'#334155',font:{family:"'Open Sans'",size:12}}}}}
    });
  };

  chartDef('chartYear','bar',d.by_year.map(r=>r.model_year),d.by_year.map(r=>r.units),['rgba(8,145,178,0.8)']);
  chartDef('chartMake','bar',d.by_make.map(r=>r.make),d.by_make.map(r=>r.units),['rgba(16,185,129,0.8)']);
  pieOpts('chartType',d.ev_type_split.map(r=>r.ev_type),d.ev_type_split.map(r=>r.units),['rgba(8,145,178,0.8)','rgba(139,92,246,0.8)']);
  chartDef('chartCounty','bar',d.by_county.map(r=>r.county),d.by_county.map(r=>r.units),['rgba(245,158,11,0.8)']);
}

// ═══════════════════════════════════════════════════════════════
// MAP
// ═══════════════════════════════════════════════════════════════
async function loadMap(){
  if(!mapInstance){
    mapInstance = L.map('map').setView([47.5, -121.8], 7);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{attribution:'CartoDB'}).addTo(mapInstance);
    // KLIK PETA → buka form rencana POM
    mapInstance.on('click', function(e){
      openAddRencanaModal(e.latlng.lat, e.latlng.lng);
    });
  }
  // Clear markers (kecuali tile layer)
  mapInstance.eachLayer(l=>{ if(l instanceof L.Marker||l instanceof L.CircleMarker) mapInstance.removeLayer(l); });

  const [evRes, locRes, pomRes] = await Promise.all([
    api(API.evFacts + '?per_page=100'),
    api(API.locations + '?per_page=200'),
    api(API.rencanaPom + '?per_page=200'),
  ]);

  const evData  = evRes.success  ? evRes.data  : [];
  const locData = locRes.success ? locRes.data : [];
  const pomData = pomRes.success ? pomRes.data : [];

  // Plot EV data
  const byLoc = {};
  evData.forEach(f=>{
    const key = `${f.latitude},${f.longitude}`;
    if(!byLoc[key]) byLoc[key]={city:f.city,county:f.county,lat:+f.latitude,lng:+f.longitude,units:0,types:new Set()};
    byLoc[key].units += +f.total_units;
    byLoc[key].types.add(f.ev_type);
  });
  Object.values(byLoc).forEach(p=>{
    const r = Math.max(8, Math.min(40, Math.sqrt(p.units/500)));
    const col = p.types.has('BEV') && p.types.has('PHEV') ? '#F59E0B' : p.types.has('BEV') ? '#0891B2' : '#8B5CF6';
    L.circleMarker([p.lat,p.lng],{radius:r,color:col,fillColor:col,fillOpacity:.6,weight:2})
      .bindPopup(`<b style="color:#0891B2;">${p.city}</b><br><small>${p.county} County</small><br>📊 ${fmt(p.units)} unit<br>🔋 ${[...p.types].join(', ')}`)
      .addTo(mapInstance);
  });

  // Plot lokasi tanpa EV
  const evCities = new Set(evData.map(f=>f.city));
  locData.filter(l=>!evCities.has(l.city)).forEach(l=>{
    L.circleMarker([+l.latitude,+l.longitude],{radius:5,color:'#10B981',fillColor:'#10B981',fillOpacity:.6,weight:1})
      .bindPopup(`<b style="color:#10B981;">${l.city}</b><br><small>${l.county} County · ${l.postal_code}</small><br><small style="color:#64748B;">Belum ada data EV</small>`)
      .addTo(mapInstance);
  });

  // Plot rencana POM
  pomData.forEach(p=>{
    const statusColor = p.status==='Selesai' ? '#10B981' : p.status==='Dalam Proses' ? '#F59E0B' : '#b20808ff';
    L.circleMarker([+p.latitude,+p.longitude],{
      radius:10, color:'#fff', fillColor:statusColor, fillOpacity:.85, weight:2
    }).bindPopup(
      `<b style="color:${statusColor};">⚡ ${p.nama_lokasi}</b><br>`+
      `<small>${p.kota}${p.kecamatan?' · '+p.kecamatan:''}</small><br>`+
      `🔌 ${p.tipe_pengisian} · ${p.kapasitas_kw} kW · ${p.jumlah_slot} slot<br>`+
      `📅 Target: ${p.target_tahun}<br>`+
      `<span style="color:${statusColor};font-weight:700;">${p.status}</span>`
    ).addTo(mapInstance);
  });
}

// ═══════════════════════════════════════════════════════════════
// CRUD LOCATIONS
// ═══════════════════════════════════════════════════════════════
let locTimer = null;
function debounceLoc(){ clearTimeout(locTimer); locTimer=setTimeout(()=>{ locState.page=1; loadLocations(); },350); }
function resetLocFilters(){ document.getElementById('locSearch').value=''; document.getElementById('locCountyFilter').value=''; locState={...locState,page:1,search:'',county:''}; loadLocations(); }
function sortLoc(col){ if(locState.sort===col) locState.dir=locState.dir==='ASC'?'DESC':'ASC'; else{locState.sort=col;locState.dir='ASC';} loadLocations(); }
function locPage(p){ locState.page=p; loadLocations(); }
function switchLocTab(view, btn){
  document.querySelectorAll('#screen-crud-locations .tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('#screen-crud-locations .tab-content').forEach(c=>c.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('locTab-'+view).classList.add('active');
  locState.view = view;
}

async function loadLocations(){
  locState.search = document.getElementById('locSearch').value;
  locState.county = document.getElementById('locCountyFilter').value;

  const params = new URLSearchParams({
    page: locState.page, per_page:15,
    search: locState.search, county: locState.county,
  });
  const res = await api(API.locations+'?'+params);
  if(!res.success){ toast('Gagal memuat lokasi','error'); return; }

  // Populate county filter (once)
  const cf = document.getElementById('locCountyFilter');
  if(cf.options.length<=1){
    const all = await api(API.locations+'?per_page=200');
    const counties = [...new Set(all.data.map(r=>r.county))].sort();
    counties.forEach(c=>{ const o=document.createElement('option'); o.value=c; o.textContent=c; cf.appendChild(o); });
    cf.value = locState.county;
  }

  // Cache for dropdowns
  if(!cacheLocations.length) {
    const all = await api(API.locations+'?per_page=200');
    cacheLocations = all.data || [];
  }

  renderLocTable(res.data);
  renderLocGrid(res.data);
  buildPagination('locPagination',res.pagination,'locPage');
  buildPagination('locPaginationGrid',res.pagination,'locPage');
}

function renderLocTable(rows){
  const tbody = document.getElementById('locTbody');
  if(!rows.length){ tbody.innerHTML=`<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📭</div><h3>Tidak ada data</h3><p>Coba ubah filter pencarian</p></div></td></tr>`; return; }
  tbody.innerHTML = rows.map(r=>`
    <tr>
      <td><span style="font-family:var(--font-mono);color:var(--text3);">${r.location_key}</span></td>
      <td style="font-weight:600;color:var(--text);">${r.city}</td>
      <td>${r.county}</td>
      <td><span class="badge badge-wa">WA</span></td>
      <td><span style="font-family:var(--font-mono);">${r.postal_code}</span></td>
      <td><span style="font-family:var(--font-mono);font-size:11px;color:var(--accent3);">${(+r.latitude).toFixed(4)}, ${(+r.longitude).toFixed(4)}</span></td>
      <td>
        <div style="display:flex;gap:6px;">
          <button class="btn btn-edit btn-sm" onclick='openEditLocationModal(${JSON.stringify(r)})'>✏️ Edit</button>
          <button class="btn btn-danger btn-sm" onclick="confirmDelete('loc',${r.location_key},'${r.city}')">🗑️</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function renderLocGrid(rows){
  const grid = document.getElementById('locGrid');
  if(!rows.length){ grid.innerHTML=`<div class="empty-state"><div class="empty-icon">📭</div><h3>Tidak ada data</h3></div>`; return; }
  grid.innerHTML = rows.map(r=>`
    <div class="loc-card">
      <div class="loc-card-city">${r.city}</div>
      <div class="loc-card-meta">${r.county} County &nbsp;·&nbsp; <span class="badge badge-wa" style="font-size:9px;padding:1px 6px;">WA</span><br>Kode Pos: <b>${r.postal_code}</b></div>
      <div class="loc-card-coords">📍 ${(+r.latitude).toFixed(4)}, ${(+r.longitude).toFixed(4)}</div>
      <div class="loc-card-actions">
        <button class="btn btn-edit btn-sm" onclick='openEditLocationModal(${JSON.stringify(r)})'>✏️ Edit</button>
        <button class="btn btn-danger btn-sm" onclick="confirmDelete('loc',${r.location_key},'${r.city}')">🗑️ Hapus</button>
      </div>
    </div>
  `).join('');
}

function buildLocationForm(data={}){
  return `
    <div class="form-group">
      <label class="form-label">Nama Kota *</label>
      <input type="text" class="form-input" id="f_city" value="${data.city||''}" placeholder="mis. Seattle">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">County *</label>
        <input type="text" class="form-input" id="f_county" value="${data.county||''}" placeholder="mis. King">
      </div>
      <div class="form-group">
        <label class="form-label">Kode Pos *</label>
        <input type="text" class="form-input" id="f_postal_code" value="${data.postal_code||''}" placeholder="mis. 98101" maxlength="10">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Latitude *</label>
        <input type="number" class="form-input" id="f_lat" value="${data.latitude||''}" placeholder="mis. 47.6062" step="0.0001">
      </div>
      <div class="form-group">
        <label class="form-label">Longitude *</label>
        <input type="number" class="form-input" id="f_lng" value="${data.longitude||''}" placeholder="mis. -122.3321" step="0.0001">
      </div>
    </div>
    <div style="background:rgba(8,145,178,.04);border:1px solid rgba(8,145,178,.15);border-radius:8px;padding:10px 14px;font-size:12px;color:var(--text3);font-family:var(--font-mono);">
      💡 State otomatis diset ke <strong style="color:var(--accent);">WA (Washington)</strong>
    </div>
  `;
}

function openAddLocationModal(){
  editingLoc = null;
  document.getElementById('locModalTitle').textContent = '➕ TAMBAH KOTA BARU';
  document.getElementById('locModalBody').innerHTML = buildLocationForm();
  openModal('locModal');
}

function openEditLocationModal(data){
  editingLoc = data.location_key;
  document.getElementById('locModalTitle').textContent = `✏️ EDIT LOKASI — ${data.city}`;
  document.getElementById('locModalBody').innerHTML = buildLocationForm(data);
  openModal('locModal');
}

async function saveLocation(){
  const body = {
    city:         document.getElementById('f_city').value.trim(),
    county:       document.getElementById('f_county').value.trim(),
    state:        'WA',
    postal_code:  document.getElementById('f_postal_code').value.trim(),
    latitude:     document.getElementById('f_lat').value,
    longitude:    document.getElementById('f_lng').value,
  };
  if(!body.city||!body.county||!body.postal_code||!body.latitude||!body.longitude){
    toast('Semua field wajib diisi!','error'); return;
  }
  const endpoint = editingLoc ? API.locations+'?id='+editingLoc : API.locations;
  const method   = editingLoc ? 'PUT' : 'POST';
  const res = await api(endpoint, method, body);
  if(res.success){
    toast(res.message, 'success');
    closeModal('locModal');
    cacheLocations = []; // invalidate cache
    loadLocations();
  } else {
    toast(res.message || 'Gagal menyimpan', 'error');
  }
}

// ═══════════════════════════════════════════════════════════════
// DELETE CONFIRM
// ═══════════════════════════════════════════════════════════════
function confirmDelete(type, id, label){
  document.getElementById('deleteMsg').innerHTML = `
    Yakin ingin menghapus <strong style="color:var(--accent4);">${label}</strong>?<br>
    <span style="font-size:12px;font-family:var(--font-mono);color:var(--text3);">Tindakan ini tidak dapat dibatalkan.</span>
  `;
  const btn = document.getElementById('confirmDeleteBtn');
  btn.onclick = () => doDelete(type, id);
  openModal('deleteModal');
}

async function doDelete(type, id){
  const endpoints = { loc: API.locations, pom: API.rencanaPom };
  const res = await api(endpoints[type]+'?id='+id, 'DELETE');
  closeModal('deleteModal');
  if(res.success){
    toast(res.message, 'success');
    if(type==='loc'){ cacheLocations=[]; loadLocations(); }
    if(type==='pom'){ loadRencanaPom(); if(mapInstance) loadMap(); }
  } else {
    toast(res.message||'Gagal menghapus','error');
  }
}

// ═══════════════════════════════════════════════════════════════
// RENCANA POM — CRUD
// ═══════════════════════════════════════════════════════════════
let pomState = {page:1, search:'', status:''};
let editingPom = null;
let pomTimer = null;

function debouncePom(){ clearTimeout(pomTimer); pomTimer=setTimeout(()=>{ pomState.page=1; loadRencanaPom(); },350); }
function resetPomFilters(){ document.getElementById('pomSearch').value=''; document.getElementById('pomStatusFilter').value=''; pomState={page:1,search:'',status:''}; loadRencanaPom(); }
function pomPage(p){ pomState.page=p; loadRencanaPom(); }

function pomStatusBadge(s){
  const map = {
    'Direncanakan': ['rgba(8,145,178,.15)','#0891B2','rgba(8,145,178,.3)','📌'],
    'Dalam Proses': ['rgba(245,158,11,.15)','#F59E0B','rgba(245,158,11,.3)','🔧'],
    'Selesai':      ['rgba(16,185,129,.15)','#10B981','rgba(16,185,129,.3)','✅'],
  };
  const [bg,color,border,icon] = map[s] || ['rgba(15,23,42,.1)','#334155','rgba(15,23,42,.2)','❓'];
  return `<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;font-family:var(--font-mono);background:${bg};color:${color};border:1px solid ${border};">${icon} ${s}</span>`;
}

function fmtRupiah(n){ return 'Rp '+Number(n||0).toLocaleString('id-ID'); }

async function loadRencanaPom(){
  pomState.search = (document.getElementById('pomSearch')?.value||'');
  pomState.status = (document.getElementById('pomStatusFilter')?.value||'');
  const params = new URLSearchParams({page:pomState.page, per_page:15, search:pomState.search, status:pomState.status});
  const res = await api(API.rencanaPom+'?'+params);
  if(!res.success){ toast('Gagal memuat data rencana POM','error'); return; }

  // Summary pills
  const all = res.data;
  const allRes = await api(API.rencanaPom+'?per_page=200');
  const allData = allRes.success ? allRes.data : [];
  document.getElementById('pomPillRencana').textContent = allData.filter(r=>r.status==='Direncanakan').length;
  document.getElementById('pomPillProses').textContent  = allData.filter(r=>r.status==='Dalam Proses').length;
  document.getElementById('pomPillSelesai').textContent = allData.filter(r=>r.status==='Selesai').length;

  // Render table
  const tbody = document.getElementById('pomTbody');
  if(!all.length){
    tbody.innerHTML=`<tr><td colspan="11"><div class="empty-state"><div class="empty-icon">⚡</div><h3>Belum ada rencana POM</h3><p>Klik lokasi di Peta GIS atau tombol Tambah Rencana</p></div></td></tr>`;
  } else {
    tbody.innerHTML = all.map((r,i)=>`
      <tr>
        <td><span style="font-family:var(--font-mono);color:var(--text3);">${(pomState.page-1)*15+i+1}</span></td>
        <td style="font-weight:600;color:var(--text);">${r.nama_lokasi}</td>
        <td><span style="color:var(--text2);">${r.kota||'—'}${r.kecamatan?' / '+r.kecamatan:''}</span></td>
        <td><span style="font-family:var(--font-mono);font-size:11px;color:var(--accent3);">${(+r.latitude).toFixed(5)}, ${(+r.longitude).toFixed(5)}</span></td>
        <td><span style="font-family:var(--font-mono);color:var(--accent);">${r.tipe_pengisian}</span></td>
        <td><span style="font-family:var(--font-mono);">${r.kapasitas_kw} kW</span></td>
        <td><span style="font-family:var(--font-mono);">${r.jumlah_slot}</span></td>
        <td><span style="font-family:var(--font-mono);color:var(--accent5);">${r.target_tahun}</span></td>
        <td><span style="font-family:var(--font-mono);font-size:12px;">${fmtRupiah(r.estimasi_biaya)}</span></td>
        <td>${pomStatusBadge(r.status)}</td>
        <td>
          <div style="display:flex;gap:6px;">
            <button class="btn btn-edit btn-sm" onclick='openEditRencanaModal(${JSON.stringify(r)})'>✏️</button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete('pom',${r.id},'${r.nama_lokasi.replace(/'/g,"\\'")}')">🗑️</button>
          </div>
        </td>
      </tr>`).join('');
  }
  buildPagination('pomPagination', res.pagination, 'pomPage');
}

function buildPomForm(data={}){
  const lat = data.latitude ? (+data.latitude).toFixed(7) : '';
  const lng = data.longitude ? (+data.longitude).toFixed(7) : '';
  const tahunNow = new Date().getFullYear();
  const tahunOpts = Array.from({length:10},(_,i)=>tahunNow+i).map(y=>`<option value="${y}" ${(data.target_tahun==y||(!data.target_tahun&&y===tahunNow+1))?'selected':''}>${y}</option>`).join('');
  const tipeOpts = ['AC','DC','AC+DC'].map(t=>`<option value="${t}" ${data.tipe_pengisian===t?'selected':''}>${t}</option>`).join('');
  const statusOpts = ['Direncanakan','Dalam Proses','Selesai'].map(s=>`<option value="${s}" ${(data.status||'Direncanakan')===s?'selected':''}>${s}</option>`).join('');
  return `
    <div class="form-group">
      <label class="form-label">Nama Lokasi *</label>
      <input type="text" class="form-input" id="pf_nama" value="${data.nama_lokasi||''}" placeholder="mis. SPKLU Jl. Sudirman No.1">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Kota</label>
        <input type="text" class="form-input" id="pf_kota" value="${data.kota||''}" placeholder="mis. Jakarta Pusat">
      </div>
      <div class="form-group">
        <label class="form-label">Kecamatan</label>
        <input type="text" class="form-input" id="pf_kecamatan" value="${data.kecamatan||''}" placeholder="mis. Tanah Abang">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Latitude *</label>
        <input type="number" class="form-input" id="pf_lat" value="${lat}" placeholder="mis. -6.2088" step="0.0000001">
      </div>
      <div class="form-group">
        <label class="form-label">Longitude *</label>
        <input type="number" class="form-input" id="pf_lng" value="${lng}" placeholder="mis. 106.8456" step="0.0000001">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Tipe Pengisian</label>
        <select class="form-select" id="pf_tipe">${tipeOpts}</select>
      </div>
      <div class="form-group">
        <label class="form-label">Kapasitas (kW)</label>
        <input type="number" class="form-input" id="pf_kapasitas" value="${data.kapasitas_kw||50}" min="1" placeholder="50">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Jumlah Slot</label>
        <input type="number" class="form-input" id="pf_slot" value="${data.jumlah_slot||2}" min="1" placeholder="2">
      </div>
      <div class="form-group">
        <label class="form-label">Target Tahun</label>
        <select class="form-select" id="pf_tahun">${tahunOpts}</select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Estimasi Biaya (Rp)</label>
        <input type="number" class="form-input" id="pf_biaya" value="${data.estimasi_biaya||0}" min="0" placeholder="500000000">
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-select" id="pf_status">${statusOpts}</select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Catatan</label>
      <textarea class="form-input" id="pf_catatan" rows="3" placeholder="Keterangan tambahan..." style="resize:vertical;">${data.catatan||''}</textarea>
    </div>
    <div style="background:rgba(8,145,178,.04);border:1px solid rgba(8,145,178,.15);border-radius:8px;padding:10px 14px;font-size:12px;color:var(--text3);font-family:var(--font-mono);">
      💡 Koordinat diisi otomatis saat klik peta. Anda juga bisa mengisi manual.
    </div>`;
}

function openAddRencanaModal(lat='', lng=''){
  editingPom = null;
  document.getElementById('pomModalTitle').textContent = '⚡ TAMBAH RENCANA POM LISTRIK';
  document.getElementById('pomModalBody').innerHTML = buildPomForm({latitude:lat, longitude:lng});
  openModal('pomModal');
}

function openEditRencanaModal(data){
  editingPom = data.id;
  document.getElementById('pomModalTitle').textContent = `✏️ EDIT RENCANA — ${data.nama_lokasi}`;
  document.getElementById('pomModalBody').innerHTML = buildPomForm(data);
  openModal('pomModal');
}

async function saveRencanaPom(){
  const body = {
    nama_lokasi:   document.getElementById('pf_nama').value.trim(),
    kota:          document.getElementById('pf_kota').value.trim(),
    kecamatan:     document.getElementById('pf_kecamatan').value.trim(),
    latitude:      document.getElementById('pf_lat').value,
    longitude:     document.getElementById('pf_lng').value,
    tipe_pengisian:document.getElementById('pf_tipe').value,
    kapasitas_kw:  document.getElementById('pf_kapasitas').value,
    jumlah_slot:   document.getElementById('pf_slot').value,
    target_tahun:  document.getElementById('pf_tahun').value,
    estimasi_biaya:document.getElementById('pf_biaya').value,
    status:        document.getElementById('pf_status').value,
    catatan:       document.getElementById('pf_catatan').value.trim(),
  };
  if(!body.nama_lokasi||!body.latitude||!body.longitude||!body.target_tahun){
    toast('Nama lokasi, koordinat, dan target tahun wajib diisi!','error'); return;
  }
  const endpoint = editingPom ? API.rencanaPom+'?id='+editingPom : API.rencanaPom;
  const method   = editingPom ? 'PUT' : 'POST';
  const res = await api(endpoint, method, body);
  if(res.success){
    toast(res.message,'success');
    closeModal('pomModal');
    loadRencanaPom();
    // refresh map markers jika peta sudah dibuka
    if(mapInstance) loadMap();
  } else {
    toast(res.message||'Gagal menyimpan','error');
  }
}

// ═══════════════════════════════════════════════════════════════
// CLOSE MODAL ON OVERLAY CLICK
// ═══════════════════════════════════════════════════════════════
document.querySelectorAll('.modal-overlay').forEach(overlay=>{
  overlay.addEventListener('click', e => { if(e.target===overlay) overlay.classList.remove('open'); });
});

// ═══════════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════════
loadDashboard();
</script>
</body>
</html>
