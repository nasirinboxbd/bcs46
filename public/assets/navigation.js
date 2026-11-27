(() => {
  const tabs = [...document.querySelectorAll('[role="tab"]')];
  const sidebar = document.getElementById('workspace-sidebar');
  const toggle = document.querySelector('.app-sidebar-toggle');
  const overlay = document.querySelector('.app-sidebar__overlay');
  if ('BroadcastChannel' in window) {
    const settingsChannel = new BroadcastChannel('bcs-site-settings');
    settingsChannel.addEventListener('message', ({ data }) => {
      if (typeof data?.siteTitle !== 'string' || typeof data.siteLogo !== 'string') return;
      document.title = data.siteTitle;
      sidebar.querySelector('.app-sidebar__brand span').textContent = data.siteTitle;
      sidebar.querySelector('.app-sidebar__mark').src = data.siteLogo;
      document.querySelector('link[rel="icon"]').href = data.siteLogo;
      if (typeof data.activeTheme === 'string') document.documentElement.dataset.theme = data.activeTheme;
      if (typeof data.colorMode === 'string') document.documentElement.dataset.colorMode = data.colorMode;
    });
  }

  function showTab() {
    const requested = window.location.hash.slice(1);
    const active = tabs.find(tab => tab.id === 'tab-' + requested) || tabs[0];

    for (const tab of tabs) {
      const selected = tab === active;
      const panel = document.getElementById(tab.getAttribute('aria-controls'));
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      panel.hidden = !selected;
      if (selected) {
        const frame = panel.querySelector('iframe');
        if (!frame.hasAttribute('src')) frame.src = frame.dataset.src;
        // Refit a preview if its tab was hidden while the window was resized.
        frame.contentWindow?.dispatchEvent(new Event('resize'));
      }
    }
  }

  function activate(tab) {
    const hash = '#' + tab.id.slice(4);
    if (window.location.hash !== hash) window.location.hash = hash;
    showTab();
  }

  for (const [index, tab] of tabs.entries()) {
    tab.addEventListener('click', () => {
      activate(tab);
      if (sidebar.classList.contains('is-open')) setSidebar(false);
    });
    tab.addEventListener('keydown', event => {
      const target = {
        ArrowUp: (index + tabs.length - 1) % tabs.length,
        ArrowDown: (index + 1) % tabs.length,
        Home: 0,
        End: tabs.length - 1
      }[event.key];
      if (target === undefined) return;
      event.preventDefault();
      tabs[target].focus();
      activate(tabs[target]);
    });
  }

  function setSidebar(open) {
    sidebar.classList.toggle('is-open', open);
    overlay.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'সাইডবার বন্ধ করুন' : 'সাইডবার খুলুন');
    (open ? sidebar.querySelector('.app-sidebar__close') : toggle).focus();
  }

  toggle.addEventListener('click', () => setSidebar(!sidebar.classList.contains('is-open')));
  overlay.addEventListener('click', () => setSidebar(false));
  sidebar.querySelector('.app-sidebar__close').addEventListener('click', () => setSidebar(false));
  window.addEventListener('hashchange', showTab);
  showTab();
})();
