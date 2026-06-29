<?php
$pageTitle = 'Settings — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin'])) {
    redirect('admin/index.php');
}
?>

<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h4 class="fw-800 mb-0" style="font-family:'Sora',sans-serif">⚙️ Platform Settings</h4>
    <div class="text small">Configure global marketplace platform.</div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-4 p-lg-5">
            <h5 class="fw-700 mb-4" style="color:var(--accent)">General Configuration</h5>
            <form>
                <div class="mb-4">
                    <label class="form-label text small fw-700">MARKETPLACE NAME</label>
                    <input type="text" class="form-control" value="Interlinked Marketplace">
                </div>
                <div class="mb-4">
                    <label class="form-label text small fw-700">ADMIN EMAIL</label>
                    <input type="email" class="form-control" value="admin@interlinked.co.za">
                </div>
                <div class="mb-4">
                    <label class="form-label text small fw-700">COMMISSION RATE (%)</label>
                    <input type="number" class="form-control" value="5">
                </div>
                <button type="button" class="btn btn-primary px-4 py-2" onclick="alert('Settings saved successfully')">Update Core Settings</button>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-4 p-lg-5 mb-4">
            <h5 class="fw-700 mb-4" style="color:var(--accent)">Security Protocol</h5>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" checked id="requireVerify">
                <label class="form-check-label small fw-600" for="requireVerify">Mandatory Seller Verification</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="maintenanceMode">
                <label class="form-check-label small fw-600" for="maintenanceMode">Maintenance Mode</label>
            </div>
        </div>

        <!-- implement these buttons - currently just alerts -->
        <div class="card p-4 p-lg-5 border-danger border-opacity-10">
            <h5 class="fw-700 mb-4 text-danger">Advanced Actions</h5>
             <p class="small" style="color: white !important;">Perform
         critical system operations. These actions are logged.</p>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-danger btn-sm text-start" onclick="alert('Database backup coming soon!')"><i data-feather="database" class="me-2" style="width:14px"></i> Backup System Database</button>
                <button class="btn btn-outline-danger btn-sm text-start" onclick="alert('Cache cleared! (not really)')"><i data-feather="trash-2" class="me-2" style="width:14px"></i> Clear Temporary Cache</button>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>
