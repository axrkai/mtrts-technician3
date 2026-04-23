<?php require __DIR__ . '/_styles.php'; ?>

<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
      My Jobs
    </h2>
    <p class="text-sm text-gray-400 mt-0.5">View and manage your assigned work orders, even when offline.</p>
  </div>

</div>

<!-- Status chips (aligned with workorders) -->
<?php
  $count_all       = count($all_work_orders ?? []);
  $count_new       = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'new'));
  $count_assigned  = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'assigned'));
  $count_scheduled = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'scheduled'));
  $count_progress  = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'in_progress'));
  $count_hold      = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'on_hold'));
  $count_resolved  = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'resolved'));
  $count_closed    = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'closed'));
  
  // Overdue: scheduled_end is past and WO is not resolved/closed
  $count_overdue = 0;
  if (!empty($all_work_orders)) {
    foreach ($all_work_orders as $w) {
      if (($w['scheduled_end'] ?? null) && 
          strtotime($w['scheduled_end']) < time() && 
          !in_array($w['status'] ?? '', ['resolved', 'closed'])) {
        $count_overdue++;
      }
    }
  }
?>
<div class="flex flex-wrap gap-2 mb-3" id="status-chips">
  <?php
  $chip_defs = [
    ''            => ['All',          $count_all],
    'new'         => ['New',          $count_new],
    'assigned'    => ['Assigned',     $count_assigned],
    'scheduled'   => ['Scheduled',    $count_scheduled],
    'in_progress' => ['In Progress',  $count_progress],
    'on_hold'     => ['On Hold',      $count_hold],
    'resolved'    => ['Resolved',     $count_resolved],
    'closed'      => ['Closed',       $count_closed],
    'overdue'     => ['Overdue',      $count_overdue],
  ];
  foreach ($chip_defs as $val => $info):
    [$label, $count] = $info;
    $is_on = ($val === '');
  ?>
  <button type="button"
    id="chip-<?= $val === '' ? 'all' : $val ?>"
    onclick="setChip('<?= $val ?>')"
    class="chip <?= $is_on ? 'chip-on' : '' ?>">
    <?= $label ?> <span class="opacity-70 font-normal">(<?= $count ?>)</span>
  </button>
  <?php endforeach; ?>
</div>

<!-- Jobs grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="jobsGrid">

  <?php if (empty($all_work_orders)): ?>
    <div class="col-span-full bg-white rounded-xl border border-gray-100 shadow-sm py-14 text-center">
      <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
        </svg>
      </div>
      <h3 class="text-sm font-semibold text-gray-700 mb-1">No work orders found</h3>
      <p class="text-xs text-gray-400 max-w-xs mx-auto">Work orders assigned to your queue will appear here. They can be unclaimed (role-based) or already assigned to you.</p>
    </div>
  <?php endif; ?>

  <?php foreach (($all_work_orders ?? []) as $wo):
    $status = (string)($wo['status'] ?? '');
    
    // Check if overdue
    $is_overdue = false;
    if (($wo['scheduled_end'] ?? null) && 
        strtotime($wo['scheduled_end']) < time() && 
        !in_array($status, ['resolved', 'closed'])) {
      $is_overdue = true;
    }

    $badge_cls = match($status) {
      'new'         => 'badge-new',
      'assigned'    => 'badge-assigned',
      'scheduled'   => 'badge-scheduled',
      'in_progress' => 'badge-in_progress',
      'on_hold'     => 'badge-on_hold',
      'resolved'    => 'badge-resolved',
      'closed'      => 'badge-closed',
      default       => 'badge-new',
    };
    $badge_label = match($status) {
      'new'         => 'New',
      'assigned'    => 'Assigned',
      'scheduled'   => 'Scheduled',
      'in_progress' => 'In Progress',
      'on_hold'     => 'On Hold',
      'resolved'    => 'Resolved',
      'closed'      => 'Closed',
      default       => ucfirst(str_replace('_', ' ', $status ?: 'new')),
    };
    $is_unclaimed = empty($wo['assigned_to']);
    
    // For filtering, use actual status or 'overdue' if overdue
    $data_status = $is_overdue ? 'overdue' : $status;
  ?>
  <div class="job-card" data-status="<?php echo htmlspecialchars($data_status); ?>">

    <div class="job-card-header pt-4">
      <div class="flex items-center justify-between mb-2">
        <span class="job-card-tag"><?php echo htmlspecialchars($wo['wo_number']); ?></span>
        <span class="wo-badge <?php echo $badge_cls; ?>">
          <span class="bdot"></span><?php echo $badge_label; ?>
        </span>
      </div>
      <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2">
        <?php echo htmlspecialchars($wo['wo_type'] ?: 'No type'); ?>
      </h3>
    </div>

    <div class="job-card-body py-3 flex-1">
      <p class="text-xs text-gray-500 line-clamp-2 mb-3">
        <?php echo htmlspecialchars($wo['notes'] ?: 'No description provided.'); ?>
      </p>

      <div class="space-y-1.5">
        <div class="job-loc">
          <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
          </svg>
          <span><?php echo htmlspecialchars($wo['building']); ?></span>
          <span class="job-loc-dot"></span>
          <span><?php echo htmlspecialchars($wo['floor']); ?></span>
          <span class="job-loc-dot"></span>
          <span><?php echo htmlspecialchars($wo['room']); ?></span>
        </div>

        <div class="flex items-center gap-1.5 text-xs text-gray-500">
          <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
          </svg>
          <span class="text-xs">Requester: <?php echo htmlspecialchars($wo['requester_name']); ?></span>
        </div>

        <?php 
        // Show assignment information if assigned_to is set
        if (!empty($wo['assigned_to'])) {
          if (!empty($wo['assigned_to_name'])) {
            // Valid assignment with name
            ?>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM9 19c-4.3-1.4-6-3.1-6-6"/>
              </svg>
              <span class="text-xs">Assigned: <?php echo htmlspecialchars($wo['assigned_to_name']); ?></span>
            </div>
            <?php
          } else {
            // Assigned but name not found (invalid user_id)
            ?>
            <div class="flex items-center gap-1.5 text-xs text-orange-500">
              <svg class="w-3.5 h-3.5 text-orange-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <span class="text-xs">Assigned: Invalid User (ID: <?php echo (int)$wo['assigned_to']; ?>)</span>
            </div>
            <?php
          }
        }
        ?>

        <?php if ($is_unclaimed): ?>
        <div class="mt-1">
          <span class="claim-badge">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/>
            </svg>
            Unclaimed in queue
          </span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="job-card-footer border-t border-gray-100 pt-3 mt-auto">
      <?php if ($is_unclaimed): ?>
        <!-- Claim button for unclaimed jobs -->
        <button type="button" 
                onclick="claimJob(<?php echo (int)$wo['wo_id']; ?>, this)"
                class="flex items-center justify-center gap-1.5 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/>
          </svg>
          Claim Job
        </button>
      <?php else: ?>
        <!-- Open Job button for claimed jobs -->
        <a href="view.php?id=<?php echo (int)$wo['wo_id']; ?>"
           class="flex items-center justify-center gap-1.5 w-full bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
          </svg>
          <?php echo $status === 'assigned' ? 'Start Work' : 'Open Job'; ?>
        </a>
      <?php endif; ?>
    </div>

  </div>
  <?php endforeach; ?>

</div>

<script>
function setChip(status) {
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('chip-on'));
  const id = status === '' ? 'chip-all' : 'chip-' + status;
  const el = document.getElementById(id);
  if (el) el.classList.add('chip-on');
  
  // Filter jobs based on status
  document.querySelectorAll('#jobsGrid [data-status]').forEach(card => {
    const show = status === '' || card.dataset.status === status;
    card.style.display = show ? '' : 'none';
  });
}

function filterJobs(filter, btn) {
  // Legacy function - redirect to setChip for compatibility
  setChip(filter);
}

function claimJob(woId, button) {
  if (!confirm('Claim this work order?')) return;
  
  button.disabled = true;
  button.textContent = 'Claiming...';
  
  fetch('<?php echo BASE_URL; ?>modules/technician/claim.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'wo_id=' + woId
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Reload the page to show updated status
      window.location.reload();
    } else {
      alert(data.message || 'Failed to claim job');
      button.disabled = false;
      button.textContent = 'Claim Job';
    }
  })
  .catch(error => {
    console.error('Claim error:', error);
    alert('Network error while claiming job');
    button.disabled = false;
    button.textContent = 'Claim Job';
  });
}

(function () {
  const chip = document.getElementById('queueChip');
  if (chip) chip.style.removeProperty('display');
})();

(function () {
  if (document.querySelector('link[rel="manifest"]')) return;
  const link = document.createElement('link');
  link.rel = 'manifest';
  link.href = '<?php echo BASE_URL; ?>public/manifest.json';
  document.head.appendChild(link);
})();

window.MRTS = {
  APP_BASE: '<?php echo BASE_URL; ?>',
  USER_ID:  <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
  ROLE_ID:  <?php echo json_encode($_SESSION['role_id'] ?? null); ?>
};
</script>

<script src="<?php echo BASE_URL; ?>public/assets/js/technician/app.js"></script>
<script src="<?php echo BASE_URL; ?>public/assets/js/technician/offline.js"></script>
<script src="<?php echo BASE_URL; ?>public/assets/js/technician/jobs.js"></script>
