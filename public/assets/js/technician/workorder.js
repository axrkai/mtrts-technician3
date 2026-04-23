// Global tab switching functions (must be outside DOMContentLoaded for onclick handlers)

// Switch secondary tabs (simplified - single tab list)
function switchSecondaryTab(secondaryKey, btn) {
  // Remove active state from all secondary buttons
  document.querySelectorAll('.secondary-tab-btn').forEach(b => b.classList.remove('tab-on'));
  
  // Add active state to current button
  if (btn) btn.classList.add('tab-on');
  
  // Hide all secondary tab panels
  document.querySelectorAll('[id^="tab-"]').forEach(p => {
    p.classList.add('hidden');
  });
  
  // Show the selected tab
  const panel = document.getElementById('tab-' + secondaryKey);
  if (panel) panel.classList.remove('hidden');
}

// Legacy compatibility - keep switchTab for any old onclick handlers
function switchTab(key, btn) {
  switchSecondaryTab(key, btn);
}

document.addEventListener('DOMContentLoaded', () => {
  const woId = (new URLSearchParams(window.location.search)).get('id') || '';
  if (!woId) {
    alert('Missing work order id');
    window.location.href = window.MRTS.APP_BASE + '/modules/technician/index.php';
    return;
  }

  // Elements
  const els = {
    status: document.getElementById('woStatusBadge'),
    number: document.getElementById('woNumber'),
    title: document.getElementById('woTitle'),
    desc: document.getElementById('woDesc'),
    priority: document.getElementById('woPriority'),
    location: document.getElementById('woLocation'),
    requester: document.getElementById('woRequester'),
    call: document.getElementById('woRequesterCall'),
    mail: document.getElementById('woRequesterMail'),
    checklistList: document.getElementById('checklistList'),
    checklistProgress: document.getElementById('checklistProgress'),
    safetyList: document.getElementById('safetyList'),
    safetyProgress: document.getElementById('safetyProgress'),
    notesList: document.getElementById('notesList'),
    noteTitle: document.getElementById('noteTitle'),
    noteText: document.getElementById('noteText'),
    beforeFiles: document.getElementById('beforeFiles'),
    afterFiles: document.getElementById('afterFiles'),
    beforeMedia: document.getElementById('beforeMedia'),
    afterMedia: document.getElementById('afterMedia'),
    beforeCount: document.getElementById('beforeCount'),
    afterCount: document.getElementById('afterCount'),
    configFiles: document.getElementById('configFiles'),
    configMedia: document.getElementById('configMedia'),
    configCount: document.getElementById('configCount'),
    partNumber: document.getElementById('partNumber'),
    partQty: document.getElementById('partQty'),
    partSerial: document.getElementById('partSerial'),
    partsList: document.getElementById('partsList'),
    signerName: document.getElementById('signerName'),
    signerId: document.getElementById('signerId'),
    signerEmail: document.getElementById('signerEmail'),
    signerPosition: document.getElementById('signerPosition'),
    signerSatisfaction: document.getElementById('signerSatisfaction'),
    signerId: document.getElementById('signerId'),
    signerEmail: document.getElementById('signerEmail'),
    signerPosition: document.getElementById('signerPosition'),
    signerSatisfaction: document.getElementById('signerSatisfaction'),
    sigCanvas: document.getElementById('sigCanvas'),
    sigStatus: document.getElementById('sigStatus'),
    btnClearSig: document.getElementById('btnClearSig'),
    btnSaveSig: document.getElementById('btnSaveSig'),
    btnSaveDraft: document.getElementById('btnSaveDraft'),
    btnComplete: document.getElementById('btnComplete'),
    blocker: document.getElementById('completeBlocker'),
    btnVoice: document.getElementById('btnVoice'),
    btnAddNote: document.getElementById('btnAddNote'),
    btnAddPart: document.getElementById('btnAddPart'),
    timerValue: document.getElementById('timerValue'),
    timerState: document.getElementById('timerState'),
    btnStart: document.getElementById('btnStart'),
    btnStop: document.getElementById('btnStop'),
    laborType: document.getElementById('laborType'),
  };

  // Tabs
  document.querySelectorAll('.tab').forEach((t) => {
    t.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach((x) => x.classList.remove('is-active'));
      document.querySelectorAll('.tab-content').forEach((x) => x.classList.add('hidden'));
      t.classList.add('is-active');
      document.getElementById('tab-' + t.getAttribute('data-tab')).classList.remove('hidden');
    });
  });

  // Local state (persisted)
  const stateKey = window.MRTS.offline.LS.cacheWorkOrderDetail(woId);
  const draftKey = `mrtsp.draft.${woId}.v1`;

  const defaultDraft = {
    woId,
    safety: {}, // id -> boolean
    checklist: {}, // id -> boolean
    notes: [], // {id, title, text, ts, source}
    evidence: { before: [], after: [] }, // {id, kind, name, blobId, dataUrl (transient), state: 'pending'|'saved'|'synced'|'error', error?: string, serverUrl?: string}
    config: [], // {id, name, blobId, dataUrl (transient), state: 'pending'|'saved'|'synced'|'error', error?: string, serverUrl?: string}
    parts: [], // {id, partNumber, qty, serial}
    timer: { running: false, startedAt: null, elapsedMs: 0, pausedMs: null, laborType: null },
    time_logs: [], // {id, labor_type, elapsed_ms, segment_ms, created_at, status}
    signoff: { signerName: '', signerId: '', signerEmail: '', signerPosition: '', signerSatisfaction: '', signatureDataUrl: null },
  };

  function loadDraft() {
  try {
  const stored = JSON.parse(localStorage.getItem(draftKey) || 'null');
  if (!stored) {
  return structuredClone(defaultDraft);
  }
  // Merge with default to ensure all keys exist
  const merged = { ...structuredClone(defaultDraft), ...stored };
  // Ensure arrays exist
  if (!merged.time_logs) merged.time_logs = [];
  if (!merged.safety) merged.safety = {};
  if (!merged.checklist) merged.checklist = {};
  return merged;
  }
  catch (e) {
  console.error('[v0] loadDraft: Error loading draft:', e);
  return structuredClone(defaultDraft);
  }
  }
  function saveDraft(next) {
  localStorage.setItem(draftKey, JSON.stringify(next));
  }

  async function migrateDraftToIndexedDB(draftObj) {
    // Convert old Base64 dataURLs to IndexedDB Blobs
    let needsSave = false;
    
    // Migrate evidence
    for (const side of ['before', 'after']) {
      for (const media of draftObj.evidence[side] || []) {
        if (media.dataUrl && !media.blobId) {
          try {
            const blobId = await window.MRTS.idbStorage.migrateDataUrlToBlob(media.dataUrl, woId);
            if (blobId) {
              media.blobId = blobId;
              delete media.dataUrl;
              needsSave = true;
            }
          } catch (e) {
            console.error('[v0] Migration failed for evidence:', e);
          }
        }
      }
    }
    
    // Migrate config files
    for (const cfg of draftObj.config || []) {
      if (cfg.dataUrl && !cfg.blobId) {
        try {
          const blobId = await window.MRTS.idbStorage.migrateDataUrlToBlob(cfg.dataUrl, woId);
          if (blobId) {
            cfg.blobId = blobId;
            delete cfg.dataUrl;
            needsSave = true;
          }
        } catch (e) {
          console.error('[v0] Migration failed for config:', e);
        }
      }
    }
    
    if (needsSave) saveDraft(draftObj);
    return draftObj;
  }

  let wo = null;
  let draft = loadDraft();
  let migrationDone = false;
  let sig = null;
  let voiceRec = null;

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function renderHeader() {
    if (!wo) return;
    els.status.textContent = (wo.status || 'pending').replace('_', ' ');
    els.status.className = `badge ${wo.status || 'pending'}`;
    els.number.textContent = wo.wo_number || wo.id;
    els.title.textContent = wo.title || '—';
    els.desc.textContent = wo.description || '—';
    els.priority.textContent = (wo.priority || 'low').toUpperCase();
    els.location.textContent = wo.location || '—';
    els.requester.textContent = (wo.requester && wo.requester.name) ? wo.requester.name : '—';
    const phone = wo.requester && wo.requester.phone ? wo.requester.phone : '';
    const email = wo.requester && wo.requester.email ? wo.requester.email : '';
    els.call.href = phone ? `tel:${phone}` : '#';
    els.mail.href = email ? `mailto:${email}` : '#';
  }

  function renderChecklist() {
    const items = (wo && wo.checklist) ? wo.checklist : [];
    
    // Check auto-verifiable items based on evidence
    const hasBeforePhoto = (draft.evidence.before || []).length > 0;
    const hasAfterPhoto = (draft.evidence.after || []).length > 0;
    const hasTimeLogged = (draft.time_logs || []).length > 0;
    
    // Auto-verify items based on evidence
    items.forEach((it) => {
      if (it.verification_type === 'photo_before' && hasBeforePhoto) {
        draft.checklist[it.id] = true;
      }
      if (it.verification_type === 'photo_after' && hasAfterPhoto) {
        draft.checklist[it.id] = true;
      }
    });
    
    const done = items.filter((it) => !!draft.checklist[it.id]).length;
    const percentage = items.length > 0 ? Math.round(done / items.length * 100) : 0;
    
    if (els.checklistProgress) {
      els.checklistProgress.innerHTML = `${done}/${items.length} items <span class="text-xs ml-1">(${percentage}%)</span>`;
    }
    
    // Update progress bar
    const progressBar = document.querySelector('.cl-progress-fill');
    if (progressBar) {
      progressBar.style.width = `${percentage}%`;
    }
    
    els.checklistList.innerHTML = items.map((it) => {
      const checked = !!draft.checklist[it.id];
      const isVerifiable = it.is_verifiable || it.verification_type;
      const isAutoVerified = isVerifiable && checked;
      
      // Determine verification status text
      let verificationStatus = '';
      if (isAutoVerified) {
        if (it.verification_type === 'photo_before') {
          verificationStatus = 'Auto-verified: Before photo captured';
        } else if (it.verification_type === 'photo_after') {
          verificationStatus = 'Auto-verified: After photo captured';
        } else {
          verificationStatus = 'Auto-verified';
        }
      }
      
      // Build badges
      let badges = '';
      if (it.required) {
        badges += '<span class="checklist-badge checklist-badge--req">Required</span>';
      }
      if (it.requires_photo) {
        badges += `<span class="checklist-badge checklist-badge--photo">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
          </svg>
          Photo
        </span>`;
      }
      
      return `
        <div class="checklist-item ${checked ? 'checklist-item--done' : ''}">
          <label class="checklist-item__row">
            <button type="button" 
                    class="checklist-item__check ${checked ? 'checklist-item__check--checked' : ''}" 
                    data-check="${it.id}"
                    ${isAutoVerified ? 'disabled title="Auto-verified based on evidence"' : ''}>
              <svg class="checklist-item__tick" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
            </button>
            <span class="checklist-item__text">${escapeHtml(it.text)}</span>
            <div class="checklist-item__badges">
              ${badges}
            </div>
          </label>
          ${checked ? `
            <div class="checklist-item__done-note">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              ${isAutoVerified ? escapeHtml(verificationStatus) : 'Completed'}
            </div>
          ` : ''}
        </div>
      `;
    }).join('');

    els.checklistList.querySelectorAll('[data-check]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (btn.disabled) return;
        const id = btn.getAttribute('data-check');
        draft.checklist[id] = !draft.checklist[id];
        saveDraft(draft);
        window.MRTS.offline.queueAction('checklist_update', woId, { itemId: id, completed: draft.checklist[id] });
        renderChecklist();
        updateCompletionBlocker();
      });
    });
    
    // Also render time logs in checklist tab
    renderChecklistTimeLogs();
  }
  
  function renderChecklistTimeLogs() {
    const container = document.getElementById('checklistTimeLogs');
    if (!container) return;
    
    const logs = draft.time_logs || [];
    if (!logs.length) {
      container.innerHTML = '<p class="text-sm text-gray-400 italic text-center py-2">Time entries will appear here</p>';
      return;
    }
    
    container.innerHTML = logs.map((log) => {
      const elapsedDisplay = log.elapsed_display || window.MRTS.fmtTime(log.elapsed_ms);
      return `
        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-600 capitalize">${escapeHtml(log.labor_type || 'other')}</span>
          </div>
          <span class="text-sm font-mono font-semibold text-olfu-green">${elapsedDisplay}</span>
        </div>
      `;
    }).join('');
  }

  function renderSafety() {
    const items = (wo && wo.safety) ? wo.safety : [];
    console.log('[v0] renderSafety: items from wo.safety:', items);
    console.log('[v0] renderSafety: safetyList element:', els.safetyList);
    const done = items.filter((it) => !!draft.safety[it.id]).length;
    const total = items.length;
    const percentage = total > 0 ? Math.round(done / total * 100) : 0;
    console.log('[v0] renderSafety: done=', done, 'total=', total, 'percentage=', percentage);
    
    if (els.safetyProgress) {
      els.safetyProgress.textContent = `${done} / ${total} complete`;
    }
    
    // Update safety progress bar
    const progressBar = document.querySelector('.safety-progress-fill');
    if (progressBar) {
      progressBar.style.width = `${percentage}%`;
    }
    
    els.safetyList.innerHTML = items.map((it) => {
      const checked = !!draft.safety[it.id];
      
      // Build badges
      let badges = '';
      if (it.mandatory) {
        badges += '<span class="checklist-badge checklist-badge--req">Required</span>';
      }
      
      return `
        <div class="checklist-item checklist-item--safety ${checked ? 'checklist-item--done' : ''}">
          <label class="checklist-item__row">
            <button type="button" 
                    class="checklist-item__check checklist-item__check--safety ${checked ? 'checklist-item__check--checked' : ''}" 
                    data-safety="${it.id}">
              <svg class="checklist-item__tick" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
            </button>
            <span class="checklist-item__text">${escapeHtml(it.text)}</span>
            <div class="checklist-item__badges">
              ${badges}
            </div>
          </label>
          ${checked ? `
            <div class="checklist-item__done-note checklist-item__done-note--safety">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Confirmed
            </div>
          ` : ''}
        </div>
      `;
    }).join('');

    els.safetyList.querySelectorAll('[data-safety]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-safety');
        draft.safety[id] = !draft.safety[id];
        saveDraft(draft);
        window.MRTS.offline.queueAction('safety_update', woId, { safetyId: id, completed: draft.safety[id] });
        renderSafety();
        updateCompletionBlocker();
      });
    });
  }

  function renderNotes() {
    const notes = draft.notes.slice().sort((a,b)=>b.ts-a.ts);
    els.notesList.innerHTML = notes.length ? notes.map((n) => `
      <div class="note">
        <div class="note__header">
          <div class="note__title">${n.title ? escapeHtml(n.title) : '<em>Untitled</em>'}</div>
          <button class="note__remove" type="button" data-remove-note="${n.id}" title="Remove note">×</button>
        </div>
        <div class="note__meta">
          <span>${escapeHtml(n.source || 'local')}</span>
          <span>${new Date(n.ts).toLocaleString()}</span>
        </div>
        <div class="note__text">${escapeHtml(n.text)}</div>
      </div>
    `).join('') : `<div class="muted">No notes yet.</div>`;
    
    // Attach remove handlers
    els.notesList.querySelectorAll('[data-remove-note]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-remove-note');
        draft.notes = draft.notes.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('note_remove', woId, { id });
        renderNotes();
      });
    });
  }

  function renderEvidence() {
    const b = draft.evidence.before;
    const a = draft.evidence.after;
    els.beforeCount.textContent = String(b.length);
    els.afterCount.textContent = String(a.length);
    els.beforeMedia.innerHTML = b.map((m) => mediaTile('before', m)).join('');
    els.afterMedia.innerHTML = a.map((m) => mediaTile('after', m)).join('');
    document.querySelectorAll('[data-media-remove]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const side = btn.getAttribute('data-side');
        const id = btn.getAttribute('data-media-remove');
        
        // Cleanup blob URLs and delete from IndexedDB
        const media = draft.evidence[side].find((x) => x.id === id);
        if (media && media.blobId && media.dataUrl && media.dataUrl.startsWith('blob:')) {
          URL.revokeObjectURL(media.dataUrl);
          window.MRTS.idbStorage.deleteBlob(media.blobId).catch((e) => {
            console.error('[v0] Failed to delete blob:', e);
          });
        }
        
        draft.evidence[side] = draft.evidence[side].filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('evidence_remove', woId, { side, id });
        renderEvidence();
        updateCompletionBlocker();
      });
    });
  }

  // Get icon SVG for file category
  function getFileIcon(filename) {
    const category = window.MRTS.idbStorage.detectFileCategory(filename);
    const icons = {
      config: `<svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>`,
      log: `<svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
      </svg>`,
      backup: `<svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
      </svg>`,
      default: `<svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>`
    };
    return icons[category] || icons.default;
  }

  // Get category label for file
  function getFileLabel(filename) {
    const category = window.MRTS.idbStorage.detectFileCategory(filename);
    const labels = { config: 'Config', log: 'Log', backup: 'Backup' };
    return labels[category] || 'File';
  }

  function renderConfig() {
    console.log('[v0] renderConfig called, items:', draft.config);
    const items = draft.config;
    els.configCount.textContent = String(items.length);
    els.configMedia.innerHTML = items.map((m) => {
      const state = m.state || 'saved';
      const fileLabel = getFileLabel(m.name);
      
      // Error state
      if (state === 'error') {
        return `
          <div class="mediaTile mediaTile--error">
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-red-50/95 rounded">
              <svg class="w-5 h-5 text-red-500 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span class="text-xs text-red-700 font-medium text-center px-1">${escapeHtml(m.error || 'Failed')}</span>
            </div>
            <button class="mediaTile__x" type="button" data-config-remove="${m.id}" aria-label="Remove">×</button>
          </div>
        `;
      }
      
      // Normal state - config files don't need dataUrl for display (just icon + filename)
      const badge = state === 'synced' ? `
        <div class="absolute top-1 right-1 bg-olfu-green rounded-full p-1">
          <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
        </div>
      ` : '';
      
      // Category badge
      const categoryBadge = `<div class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-gray-800/70 text-white text-xs rounded font-medium">${fileLabel}</div>`;
      
      return `
        <div class="mediaTile">
          <div class="mediaTile__content flex flex-col items-center justify-center h-full">
            ${getFileIcon(m.name)}
            <div class="text-xs text-gray-600 mt-1 truncate max-w-full px-1" title="${escapeHtml(m.name)}">${escapeHtml(m.name)}</div>
          </div>
          ${categoryBadge}
          ${badge}
          <button class="mediaTile__x" type="button" data-config-remove="${m.id}" aria-label="Remove">×</button>
        </div>
      `;
    }).join('');

    els.configMedia.querySelectorAll('[data-config-remove]').forEach((b) => {
      b.addEventListener('click', () => {
        const id = b.getAttribute('data-config-remove');
        
        // Cleanup blob URLs and delete from IndexedDB
        const item = draft.config.find((x) => x.id === id);
        if (item && item.blobId && item.dataUrl && item.dataUrl.startsWith('blob:')) {
          URL.revokeObjectURL(item.dataUrl);
          window.MRTS.idbStorage.deleteBlob(item.blobId).catch(() => {});
        }
        
        draft.config = draft.config.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('config_remove', woId, { id });
        renderConfig();
      });
    });
  }

  function mediaTile(side, m) {
    const isVideo = m.kind === 'video';
    const srcAttr = m.dataUrl ? `src="${m.dataUrl}"` : '';
    const state = m.state || 'saved';
    
    // Show error state
    if (state === 'error') {
      return `
        <div class="mediaTile mediaTile--error">
          <div class="absolute inset-0 flex flex-col items-center justify-center bg-red-50/95 rounded">
            <svg class="w-6 h-6 text-red-500 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-xs text-red-700 font-medium text-center px-2">${escapeHtml(m.error || 'Upload failed')}</span>
          </div>
          <button class="mediaTile__x" type="button" data-side="${side}" data-media-remove="${m.id}" aria-label="Remove">×</button>
        </div>
      `;
    }
    
    // Show loading/processing state
    if (!m.dataUrl) {
      return `
        <div class="mediaTile mediaTile--loading">
          <div class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50/95 rounded">
            <div class="w-4 h-4 border-2 border-gray-300 border-t-olfu-green rounded-full animate-spin mb-1"></div>
            <span class="text-xs text-gray-600 font-medium">${state === 'saved' ? 'Processing...' : 'Syncing...'}</span>
          </div>
          <button class="mediaTile__x" type="button" data-side="${side}" data-media-remove="${m.id}" aria-label="Remove">×</button>
        </div>
      `;
    }
    
    // Normal state
    const inner = isVideo
      ? `<video ${srcAttr} muted playsinline controls></video>`
      : `<img ${srcAttr} alt="${escapeHtml(m.name || 'photo')}" />`;
    
    // Add checkmark for synced state
    const badge = state === 'synced' ? `
      <div class="absolute top-1 right-1 bg-olfu-green rounded-full p-1">
        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
      </div>
    ` : '';
    
    return `
      <div class="mediaTile">
        ${inner}
        ${badge}
        <button class="mediaTile__x" type="button" data-side="${side}" data-media-remove="${m.id}" aria-label="Remove">×</button>
      </div>
    `;
  }

  async function filesToEvidence(files, side) {
    const list = Array.from(files || []);
    const errors = [];
    
    for (const f of list) {
      const kind = (f.type || '').startsWith('video/') ? 'video' : 'image';
      const itemId = `m_${Date.now()}_${Math.random().toString(16).slice(2)}`;
      
      try {
        // Validate file first
        window.MRTS.idbStorage.validateFile(f, kind);
        
        // Save blob to IndexedDB, get back the blobId
        const blobId = await window.MRTS.idbStorage.saveBlobAndGetId(woId, f, kind);
        
        // Store reference to blob in draft with state tracking
        const item = { id: itemId, kind, name: f.name, blobId, state: 'saved' };
        draft.evidence[side].push(item);
        
        // Queue sync action with blobId in metadata
        window.MRTS.offline.queueAction('evidence_add', woId, { side, kind, name: f.name }, { hasBlob: true, blobId });
      } catch (e) {
        const errorMsg = e.message || 'Failed to save file';
        errors.push(`${f.name}: ${errorMsg}`);
        // Still add item to draft but mark as error
        const item = { id: itemId, kind, name: f.name, state: 'error', error: errorMsg };
        draft.evidence[side].push(item);
      }
    }
    
    saveDraft(draft);
    await loadBlobUrlsForEvidence();
    renderEvidence();
    updateCompletionBlocker();
    
    // Show errors if any
    if (errors.length > 0) {
      alert('Some files failed to upload:\n' + errors.join('\n'));
    }
  }

  // Config uploads use EXACT same pattern as evidence uploads
  async function filesToConfig(files) {
    console.log('[v0] filesToConfig START - files:', files, 'length:', files?.length);
    const list = Array.from(files || []);
    console.log('[v0] filesToConfig list:', list);
    const errors = [];
    
    for (const f of list) {
      const itemId = `c_${Date.now()}_${Math.random().toString(16).slice(2)}`;
      console.log('[v0] Processing file:', f.name, 'size:', f.size, 'type:', f.type);
      
      try {
        // Save blob to IndexedDB - no extra validation, just like evidence
        // The file input's accept attribute already limits file types
        console.log('[v0] Calling saveBlobAndGetId...');
        const blobId = await window.MRTS.idbStorage.saveBlobAndGetId(woId, f, 'config');
        console.log('[v0] Got blobId:', blobId);
        
        // Store reference to blob in draft with state tracking (same structure as evidence)
        const item = { id: itemId, name: f.name, blobId, state: 'saved' };
        draft.config.push(item);
        console.log('[v0] Added to draft.config, length:', draft.config.length);
        
        // Queue sync action with blobId in metadata
        window.MRTS.offline.queueAction('config_add', woId, { name: f.name }, { hasBlob: true, blobId });
        console.log('[v0] Queued sync action');
      } catch (e) {
        console.error('[v0] Error in filesToConfig:', e);
        const errorMsg = e.message || 'Failed to save file';
        errors.push(`${f.name}: ${errorMsg}`);
        // Still add item to draft but mark as error
        const item = { id: itemId, name: f.name, state: 'error', error: errorMsg };
        draft.config.push(item);
      }
    }
    
    console.log('[v0] Saving draft...');
    saveDraft(draft);
    console.log('[v0] Loading blob URLs...');
    await loadBlobUrlsForConfig();  // Load URLs BEFORE render, just like evidence
    console.log('[v0] Calling renderConfig...');
    renderConfig();
    console.log('[v0] filesToConfig COMPLETE');
    
    // Reset the file input to allow re-uploading the same file
    els.configFiles.value = '';
    
    // Show errors if any
    if (errors.length > 0) {
      alert('Some files failed to upload:\n' + errors.join('\n'));
    }
  }

  // Generate ObjectURLs from IndexedDB Blobs for config files (same pattern as evidence)
  async function loadBlobUrlsForConfig() {
    const processConfig = async (item) => {
      if (item.blobId && !item.dataUrl) {
        const url = await window.MRTS.idbStorage.getBlobAsUrl(item.blobId);
        if (url) item.dataUrl = url;
      }
    };
    
    const promises = (draft.config || []).map(processConfig);
    await Promise.all(promises);
  }

  async function loadBlobUrlsForEvidence() {
    // Generate ObjectURLs from IndexedDB Blobs for rendering
    const processMedia = async (media) => {
      if (media.blobId && !media.dataUrl) {
        const url = await window.MRTS.idbStorage.getBlobAsUrl(media.blobId);
        if (url) media.dataUrl = url;
      }
    };
    
    const beforePromises = draft.evidence.before.map(processMedia);
    const afterPromises = draft.evidence.after.map(processMedia);
    await Promise.all([...beforePromises, ...afterPromises]);
  }

  // Called by offline.js after sync response to update draft items with server URLs
  window.updateDraftItemAfterSync = function(itemId, action, serverUrl) {
    if (action === 'evidence_add') {
      const media = draft.evidence.before.find((m) => m.id === itemId) || draft.evidence.after.find((m) => m.id === itemId);
      if (media) {
        media.state = 'synced';
        media.serverUrl = serverUrl;
      }
    } else if (action === 'config_add') {
      const item = draft.config.find((c) => c.id === itemId);
      if (item) {
        item.state = 'synced';
        item.serverUrl = serverUrl;
      }
    }
    saveDraft(draft);
    renderEvidence();
    renderConfig();
  };

  // Called by offline.js when sync fails for an item
  window.updateDraftItemError = function(itemId, action, errorMessage) {
    if (action === 'evidence_add') {
      const media = draft.evidence.before.find((m) => m.id === itemId) || draft.evidence.after.find((m) => m.id === itemId);
      if (media) {
        media.state = 'error';
        media.error = errorMessage;
      }
    } else if (action === 'config_add') {
      const item = draft.config.find((c) => c.id === itemId);
      if (item) {
        item.state = 'error';
        item.error = errorMessage;
      }
    }
    saveDraft(draft);
    renderEvidence();
    renderConfig();
  };

  function renderParts() {
    els.partsList.innerHTML = draft.parts.length ? draft.parts.map((p) => `
      <div class="row">
        <div class="row__main">
          <div class="row__title">${escapeHtml(p.partNumber)} ×${p.qty}</div>
          <div class="row__sub">${p.serial ? `Serial: ${escapeHtml(p.serial)}` : 'No serial'}</div>
        </div>
        <button class="btn btn--danger btn--sm" type="button" data-part-remove="${p.id}">Remove</button>
      </div>
    `).join('') : `<div class="muted">No parts added.</div>`;

    document.querySelectorAll('[data-part-remove]').forEach((b) => {
      b.addEventListener('click', () => {
        const id = b.getAttribute('data-part-remove');
        draft.parts = draft.parts.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('part_remove', woId, { id });
        renderParts();
      });
    });
  }

  // Timer variables
  let timerSeconds = 0;
  let timerRunning = false;
  let timerInterval = null;

  function formatTime(sec) {
    const h = String(Math.floor(sec / 3600)).padStart(2, '0');
    const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
    const s = String(sec % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
  }

  function updateTimerDisplay() {
    els.timerValue.textContent = formatTime(timerSeconds);
  }

  function startTimer() {
    console.log('[v0] startTimer: timerRunning=', timerRunning, 'timerSeconds=', timerSeconds);
    
    if (!timerRunning) {
      // Validate labor type on first start only
      if (timerSeconds === 0) {
        const laborType = (els.laborType.value || '').trim();
        if (!laborType) {
          alert('Please select a labor type before starting the timer');
          return;
        }
        draft.timer.laborType = laborType;
        console.log('[v0] startTimer: labor type set to', laborType);
      }
      
      timerRunning = true;
      draft.timer.running = true;
      draft.timer.startedAt = Date.now();
      
      timerInterval = setInterval(() => {
        timerSeconds++;
        updateTimerDisplay();
      }, 1000);
      
      els.timerState.textContent = 'Running';
      els.btnStart.textContent = 'PAUSE';
      els.btnStart.classList.remove('bg-olfu-green', 'hover:bg-olfu-green-md');
      els.btnStart.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
      els.btnStop.disabled = false;
      
      saveDraft(draft);
      window.MRTS.offline.queueAction('time_start', woId, { labor_type: draft.timer.laborType });
      console.log('[v0] startTimer: timer started');
      updateCompletionBlocker();
    } else {
      // Pause
      console.log('[v0] startTimer: pausing timer');
      timerRunning = false;
      draft.timer.running = false;
      draft.timer.elapsedMs = timerSeconds * 1000;
      
      clearInterval(timerInterval);
      els.timerState.textContent = 'Paused';
      els.btnStart.textContent = 'START';
      els.btnStart.classList.remove('bg-yellow-500', 'hover:bg-yellow-600');
      els.btnStart.classList.add('bg-olfu-green', 'hover:bg-olfu-green-md');
      
      saveDraft(draft);
      window.MRTS.offline.queueAction('time_pause', woId, { elapsedSeconds: timerSeconds });
      console.log('[v0] startTimer: paused at', timerSeconds, 'seconds');
      updateCompletionBlocker();
    }
  }

  function stopTimer() {
    if (timerSeconds === 0) {
      return;
    }
    
    // Clear interval if running
    if (timerRunning) {
      clearInterval(timerInterval);
      timerRunning = false;
    }
    
    // Finalize elapsed time
    const elapsedMs = timerSeconds * 1000;
    const elapsedFormatted = window.MRTS.fmtTime(elapsedMs);
    
    // Initialize time_logs array if needed
    if (!draft.time_logs) {
      draft.time_logs = [];
    }
    
    // Create time log entry with timestamp
    const now = new Date();
    const timeLogId = `tl_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    const timeLogEntry = {
      id: timeLogId,
      labor_type: draft.timer.laborType || 'other',
      elapsed_ms: elapsedMs,
      elapsed_display: elapsedFormatted,
      created_at: now.toISOString(),
      created_at_display: now.toLocaleString('en-US', { 
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      }),
      status: 'draft'
    };
    
    // Push to draft
    draft.time_logs.push(timeLogEntry);
    
    // Update draft.timer state
    draft.timer.running = false;
    draft.timer.startedAt = null;
    draft.timer.laborType = null;
    draft.timer.elapsedMs = 0;
    draft.timer.pausedMs = null;
    
    // Queue action and save draft BEFORE resetting UI
    window.MRTS.offline.queueAction('time_stop', woId, { 
      total_elapsed_ms: elapsedMs,
      labor_type: timeLogEntry.labor_type
    });
    saveDraft(draft);
    
    // Reset UI timer variables
    timerSeconds = 0;
    
    // Update timer display and state
    els.timerState.textContent = 'Not started';
    els.btnStart.textContent = 'START';
    els.btnStart.classList.remove('bg-yellow-500', 'hover:bg-yellow-600');
    els.btnStart.classList.add('bg-olfu-green', 'hover:bg-olfu-green-md');
    els.btnStop.disabled = true;
    
    // Update display
    updateTimerDisplay();
    
    // Render the time logs list
    renderTimeLogs();
    
    updateCompletionBlocker();
  }

  function renderTimeLogs() {
    const logs = draft.time_logs || [];
    const container = document.getElementById('timeLogsList');
    if (!container) {
      console.log('[v0] renderTimeLogs: timeLogsList container not found');
      return;
    }
    
    console.log('[v0] renderTimeLogs: rendering', logs.length, 'entries');
    
    if (!logs.length) {
      container.innerHTML = '<div class="text-xs text-gray-500">No time entries yet.</div>';
      return;
    }
    
    container.innerHTML = logs.map((log) => {
      const elapsedDisplay = log.elapsed_display || window.MRTS.fmtTime(log.elapsed_ms);
      const createdDisplay = log.created_at_display || new Date(log.created_at).toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      });
      
      return `
        <div class="timeLog">
          <div class="timeLog__header">
            <span class="timeLog__type">${escapeHtml(log.labor_type || 'other')}</span>
            <span class="timeLog__time">${elapsedDisplay}</span>
          </div>
          <div class="timeLog__timestamp text-xs text-gray-500">${createdDisplay}</div>
          <button class="timeLog__remove" type="button" data-remove-timelog="${log.id}" title="Remove">×</button>
        </div>
      `;
    }).join('');
    
    console.log('[v0] renderTimeLogs: HTML rendered, adding event listeners');
    
    container.querySelectorAll('[data-remove-timelog]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-remove-timelog');
        console.log('[v0] removing time log:', id);
        draft.time_logs = draft.time_logs.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('time_log_remove', woId, { id });
        renderTimeLogs();
      });
    });
    
    console.log('[v0] renderTimeLogs: complete');
  }

  function renderSignoff() {
    els.signerName.value        = draft.signoff.signerName        || '';
    if (els.signerId)           els.signerId.value           = draft.signoff.signerId        || '';
    if (els.signerEmail)        els.signerEmail.value        = draft.signoff.signerEmail      || '';
    if (els.signerPosition)     els.signerPosition.value     = draft.signoff.signerPosition   || '';
    if (els.signerSatisfaction) els.signerSatisfaction.value = draft.signoff.signerSatisfaction || '';
    updateSigStatus();
  }

  function updateSigStatus() {
    const dot  = els.sigStatus.querySelector('span:first-child');
    const text = els.sigStatus.querySelector('span:last-child');
    if (draft.signoff.signatureDataUrl) {
      if (dot)  { dot.style.background  = '#15803d'; }
      if (text) { text.textContent = 'Signature saved'; text.style.color = '#15803d'; }
    } else {
      if (dot)  { dot.style.background  = '#f87171'; }
      if (text) { text.textContent = 'Not signed'; text.style.color = '#6b7280'; }
    }
  }

  function updateCompletionBlocker() {
    const reasons = validateCompletion();
    if (reasons.length) {
      els.blocker.style.display = 'block';
      els.blocker.className = 'alert alert--warn';
      els.blocker.innerHTML = `<strong>Cannot complete yet:</strong><ul style="margin:8px 0 0 18px">${reasons.map(r=>`<li>${escapeHtml(r)}</li>`).join('')}</ul>`;
      return false;
    }
    els.blocker.style.display = 'none';
    return true;
  }

  function validateCompletion() {
    const reasons = [];
    
    // CRITICAL FIX: Reload draft from localStorage to ensure we see the latest time entries
    // The draft object in memory may be stale if time entries were added/saved since page load
    const freshDraft = loadDraft();
    
    // Debug: Log safety check status
    const safetyItems = (wo && wo.safety) ? wo.safety : [];
    const safetyRequired = safetyItems.filter((it) => it.mandatory);
    const safetyDone = safetyRequired.every((it) => !!freshDraft.safety[it.id]);
    if (safetyRequired.length && !safetyDone) reasons.push('Complete all required safety checks');

    // Debug: Log checklist status
    const items = (wo && wo.checklist) ? wo.checklist : [];
    const required = items.filter((it) => it.required);
    const reqDone = required.every((it) => !!freshDraft.checklist[it.id]);
    if (required.length && !reqDone) reasons.push('Complete all required checklist items');

    const hasEvidence = (freshDraft.evidence.before.length + freshDraft.evidence.after.length) > 0;
    if (wo && wo.evidence_required && !hasEvidence) reasons.push('Add at least one photo/video evidence');

    const hasSig = !!freshDraft.signoff.signatureDataUrl;
    if (wo && wo.signature_required && !hasSig) reasons.push('Capture requester signature');

    // Check if time was tracked (either currently running or has logged entries)
    // Uses freshDraft to ensure we pick up latest time entries from localStorage
    const hasTimeLogs = (freshDraft.time_logs || []).length > 0;
    const hasTime = timerSeconds > 0 || hasTimeLogs;
    if (!hasTime) reasons.push('Start time tracking');

    return reasons;
  }

  // Wire actions
  els.btnVoice.addEventListener('click', () => {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
      alert('Voice recognition not supported in this browser');
      return;
    }
    const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;
    recognition.start();
    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      els.noteText.value += (els.noteText.value ? ' ' : '') + transcript;
    };
    recognition.onerror = (event) => {
      alert('Voice recognition error: ' + event.error);
    };
  });

  els.beforeFiles.addEventListener('change', (e) => filesToEvidence(e.target.files, 'before'));
  els.afterFiles.addEventListener('change', (e) => filesToEvidence(e.target.files, 'after'));
  
  console.log('[v0] Setting up configFiles listener, element:', els.configFiles);
  els.configFiles.addEventListener('change', (e) => {
    console.log('[v0] configFiles change event fired!', e.target.files);
    filesToConfig(e.target.files);
  });

  els.btnAddPart.addEventListener('click', () => {
    const partNumber = (els.partNumber.value || '').trim();
    const qty = Math.max(1, Number(els.partQty.value || 1));
    const serial = (els.partSerial.value || '').trim();
    if (!partNumber) return;
    const item = { id: `p_${Date.now()}_${Math.random().toString(16).slice(2)}`, partNumber, qty, serial };
    draft.parts.push(item);
    els.partNumber.value = '';
    els.partQty.value = '1';
    els.partSerial.value = '';
    saveDraft(draft);
    window.MRTS.offline.queueAction('part_add', woId, item);
    renderParts();
  });

  // Verify buttons exist
  if (!els.btnStart) console.error('[v0] btnStart element not found!');
  if (!els.btnStop) console.error('[v0] btnStop element not found!');
  
  if (els.btnStart) {
    els.btnStart.addEventListener('click', () => {
      console.log('[v0] btnStart clicked');
      startTimer();
    });
  }
  
  if (els.btnStop) {
    els.btnStop.addEventListener('click', () => {
      console.log('[v0] btnStop clicked');
      stopTimer();
    });
  }

  // Voice-to-text (best-effort)
  els.btnVoice.addEventListener('click', () => {
    const Speech = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Speech) {
      alert('Voice-to-text not supported in this browser');
      return;
    }
    if (voiceRec) {
      try { voiceRec.stop(); } catch {}
      voiceRec = null;
      els.btnVoice.textContent = 'Voice';
      return;
    }
    const rec = new Speech();
    rec.lang = 'en-US';
    rec.interimResults = true;
    rec.onresult = (evt) => {
      let txt = '';
      for (let i = evt.resultIndex; i < evt.results.length; i++) {
        txt += evt.results[i][0].transcript;
      }
      els.noteText.value = (els.noteText.value ? els.noteText.value + ' ' : '') + txt.trim();
    };
    rec.onend = () => {
      voiceRec = null;
      els.btnVoice.textContent = 'Voice';
    };
    rec.start();
    voiceRec = rec;
    els.btnVoice.textContent = 'Stop voice';
  });

  // Add Note
  els.btnAddNote.addEventListener('click', () => {
    const title = (els.noteTitle.value || '').trim();
    const text = (els.noteText.value || '').trim();
    if (!text) {
      alert('Please enter a note');
      return;
    }
    const noteId = `n_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    draft.notes.push({
      id: noteId,
      title: title,
      text: text,
      ts: Date.now(),
      source: 'local'
    });
    els.noteTitle.value = '';
    els.noteText.value = '';
    saveDraft(draft);
    window.MRTS.offline.queueAction('note_add', woId, { title, text });
    renderNotes();
    updateCompletionBlocker();
  });

  // Sign-off — wire all fields
  function wireSignoffField(el, key) {
    if (!el) return;
    el.addEventListener('input', () => {
      draft.signoff[key] = el.value;
      saveDraft(draft);
    });
  }
  wireSignoffField(els.signerName,        'signerName');
  wireSignoffField(els.signerId,          'signerId');
  wireSignoffField(els.signerEmail,       'signerEmail');
  wireSignoffField(els.signerPosition,    'signerPosition');
  wireSignoffField(els.signerSatisfaction,'signerSatisfaction');

  sig = window.MRTS.signature.setup(els.sigCanvas);
  els.btnClearSig.addEventListener('click', () => {
    sig.clear();
    draft.signoff.signatureDataUrl = null;
    saveDraft(draft);
    updateSigStatus();
    window.MRTS.offline.queueAction('signature_clear', woId, {});
    updateCompletionBlocker();
  });
  els.btnSaveSig.addEventListener('click', () => {
    if (sig.isBlank()) {
      alert('Please draw your signature first');
      return;
    }
    draft.signoff.signatureDataUrl = sig.toDataUrl();
    saveDraft(draft);
    updateSigStatus();
    window.MRTS.offline.queueAction('signature_save', woId, { hasSignature: true }, { hasBlob: true });
    updateCompletionBlocker();
  });

  els.btnSaveDraft.addEventListener('click', () => {
    saveDraft(draft);
    window.MRTS.offline.queueAction('draft_save', woId, {});
    alert('Draft saved (local + queued)');
  });

  els.btnComplete.addEventListener('click', async () => {
    const ok = updateCompletionBlocker();
    if (!ok) return;
    // Gather time logs and calculate total
    const totalTimeMs = draft.timer.elapsedMs + (draft.timer.running ? (Date.now() - draft.timer.startedAt) : 0);
    const completionPayload = {
      summary: 'Completed in prototype',
      time_logs: draft.time_logs || [],
      total_time_ms: totalTimeMs,
      signer_name:         draft.signoff.signerName,
      signer_id:           draft.signoff.signerId,
      signer_email:        draft.signoff.signerEmail,
      signer_position:     draft.signoff.signerPosition,
      signer_satisfaction: draft.signoff.signerSatisfaction,
      signature: draft.signoff.signatureDataUrl ? 'yes' : 'no'
    };
    // Queue completion action and attempt sync if online
    window.MRTS.offline.queueAction('workorder_complete', woId, completionPayload);
    saveDraft(draft);
    try {
      const online = await window.MRTS.offline.isReallyOnline();
      if (online) await window.MRTS.offline.syncNow();
      alert('Work order marked complete (prototype).');
      window.location.href = window.MRTS.APP_BASE + '/modules/technician/index.php';
    } catch (e) {
      alert(e.message || 'Complete queued (offline)');
      window.location.href = window.MRTS.APP_BASE + '/modules/technician/index.php';
    }
  });

  // Load WO from PHP data
  async function load() {
    // #region agent log
    fetch('http://127.0.0.1:7640/ingest/480ae408-6ba6-451f-aaef-9603744d9d28',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'30aee9'},body:JSON.stringify({sessionId:'30aee9',runId:'pre-fix',hypothesisId:'H_WO_DATA',location:'public/assets/js/technician/workorder.js:562',message:'WO bootstrap data presence',data:{has_WO_DATA:!!window.__WO_DATA__,has_WO_ID:typeof window.__WO_ID__!=='undefined',has_MRTS:!!window.MRTS,app_base:(window.MRTS&&window.MRTS.APP_BASE)||null},timestamp:Date.now()})}).catch(()=>{});
    // #endregion
    wo = window.__WO_DATA__;
    if (!wo) {
      alert('Work order data not available.');
      window.location.href = window.MRTS.APP_BASE + '/modules/technician/index.php';
      return;
    }

    console.log('[v0] WO loaded from __WO_DATA__:', {
      safety_count: (wo.safety || []).length,
      safety: wo.safety,
      checklist_count: (wo.checklist || []).length,
      checklist: wo.checklist,
      time_logs_count: (wo.time_logs || []).length
    });

    // Migrate old Base64 dataURLs to IndexedDB on first load
    if (!migrationDone) {
      draft = await migrateDraftToIndexedDB(draft);
      migrationDone = true;
    }

    console.log('[v0] Draft loaded before render:', {
      safety: draft.safety,
      checklist: draft.checklist,
      time_logs: draft.time_logs
    });

    renderHeader();
    renderSafety();
    renderChecklist();
    renderNotes();
    await loadBlobUrlsForEvidence();
    await loadBlobUrlsForConfig();
    renderEvidence();
    renderConfig();
    renderParts();
    renderSignoff();
    // Note: timerSeconds, timerRunning, timerInterval are declared at module scope (line 549-551)
    renderTimeLogs();
    updateTimerDisplay();
    updateCompletionBlocker();
  }

  load();
  
  // Expose debug utilities to window for testing
  window.timerDebug = {
    getState: () => ({
      timerSeconds,
      timerRunning,
      draftTimer: draft?.timer,
      timeLogs: draft?.time_logs?.length
    }),
    testStop: () => stopTimer(),
    testStart: () => startTimer(),
    getDraft: () => draft
  };
  console.log('[v0] Timer debug utilities available at window.timerDebug');
});

if (draft.timer) {
  timerSeconds = Math.floor((draft.timer.elapsedMs || 0) / 1000);

  if (draft.timer.running && draft.timer.startedAt) {
    const extra = Math.floor((Date.now() - draft.timer.startedAt) / 1000);
    timerSeconds += extra;

    timerRunning = true;
    timerInterval = setInterval(() => {
      timerSeconds++;
      updateTimerDisplay();
    }, 1000);

    els.timerState.textContent = 'Running';
    els.btnStart.textContent = 'PAUSE';
    els.btnStop.disabled = false;
  }
}
