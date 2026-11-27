(function () {
  const KEY = 'bcs-shared-candidate-profile-v1';
  const $ = (id) => document.getElementById(id);
  const text = (id) => ($(id) ? String($(id).value || '').trim() : '');
  const optionText = (id) => {
    const el = $(id);
    return el && el.selectedOptions[0] ? el.selectedOptions[0].textContent.trim() : '';
  };
  const normalizeCadre = (value) => String(value || '')
    .replace(/^বিসিএস\s*\((.*)\)$/, '$1')
    .replace(/পররাষ্ট্র বিষয়ক/g, 'পররাষ্ট্র')
    .replace(/\s+/g, ' ')
    .trim();
  const findCadreOption = (select, data) => {
    const wanted = [data.cadreId, data.cadreLabel].filter(Boolean).map(normalizeCadre);
    return [...select.options].find((option) =>
      wanted.includes(normalizeCadre(option.value)) || wanted.includes(normalizeCadre(option.textContent))
    );
  };
  function read() {
    const main = $('form');
    const joining = $('f');
    const cadreId = main && $('cadre') ? text('cadre') : text('cadreSel');
    return {
      name: text('name'),
      merit: text('merit'),
      reg: text('reg'),
      cadreId,
      cadreLabel: joining && cadreId === '__other' ? text('cadreTxt') : (main && $('cadre') ? optionText('cadre') : optionText('cadreSel')),
      postId: main && $('post') ? text('post') : text('postSel'),
      postLabel: main && $('post') ? optionText('post') : optionText('postSel'),
      savedAt: new Date().toISOString(),
      source: joining ? 'joining-letter' : 'declaration'
    };
  }
  function hasCandidate(data) { return data && [data.name, data.merit, data.reg, data.cadreLabel, data.postLabel].some(Boolean); }
  let restoring = false;
  function save() {
    if (restoring) return;
    try { localStorage.setItem(KEY, JSON.stringify(read())); } catch (_) {}
  }
  function load() {
    let data;
    try { data = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (_) { data = null; }
    if (!hasCandidate(data)) return;
    ['name', 'merit', 'reg'].forEach((id) => {
      if ($(id) && data[id]) {
        $(id).value = data[id];
        $(id).dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
    if ($('cadreSel')) {
      let option = findCadreOption($('cadreSel'), data);
      if (!option && data.cadreLabel) option = [...$('cadreSel').options].find((o) => o.value === '__other');
      if (option) {
        $('cadreSel').value = option.value;
        if (option.value === '__other' && $('cadreTxt')) $('cadreTxt').value = normalizeCadre(data.cadreLabel);
        $('cadreSel').dispatchEvent(new Event('change', { bubbles: true }));
      }
      const post = [...$('postSel').options].find((o) => o.value === data.postId || o.textContent.trim() === data.postLabel || (data.postLabel && o.textContent.includes(data.postLabel)));
      if (post) { $('postSel').value = post.value; $('postSel').dispatchEvent(new Event('change', { bubbles: true })); }
    }
    if ($('cadre')) {
      const option = findCadreOption($('cadre'), data);
      if (option) {
        $('cadre').value = option.value;
        $('cadre').dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
    if ($('post')) {
      const option = [...$('post').options].find((o) => o.value === data.postId || o.textContent.trim() === data.postLabel || o.textContent.includes(data.postLabel));
      if (option) {
        $('post').value = option.value;
        $('post').dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }
  function addNotice() {
    const form = $('form') || $('f');
    if (!form || document.querySelector('.shared-profile-note')) return;
    const note = document.createElement('div');
    note.className = 'shared-profile-note';
    note.innerHTML = '<span aria-hidden="true">🔁</span><div><strong>একবার তথ্য দিন, সব ডকুমেন্টে ব্যবহার করুন</strong>নাম, মেধাক্রম, রেজিস্ট্রেশন, ক্যাডার ও পদ এই ব্রাউজারে সংরক্ষিত থাকবে। <a href="joining-letter">যোগদানপত্রে যান</a></div>';
    const link = note.querySelector('a');
    if ($('f')) {
      link.href = './#declaration';
      link.textContent = 'অঙ্গীকারনামায় যান';
    }
    link.addEventListener('click', (event) => {
      if (window.parent === window) return;
      event.preventDefault();
      window.parent.location.hash = $('f') ? '#declaration' : '#joining';
    });
    form.parentNode.insertBefore(note, form);
  }
  function init() {
    load();
    addNotice();
    document.addEventListener('input', (event) => { if (event.target.closest('#form, #f')) save(); });
    document.addEventListener('change', (event) => { if (event.target.closest('#form, #f')) save(); });
    window.addEventListener('storage', (event) => {
      if (event.key !== KEY) return;
      restoring = true;
      try { load(); } finally { restoring = false; }
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
