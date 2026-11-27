(() => {
  const controls = [...document.querySelectorAll("[data-preview-target]")];
  if (!controls.length) return;

  const labelFor = (open) => open ? "✕ পূর্বরূপ বন্ধ করুন" : "⛶ পূর্ণ পর্দায় দেখুন";
  const isOpen = (panel) => document.fullscreenElement === panel || panel.classList.contains("is-fullscreen");

  function sync(panel, button) {
    const open = isOpen(panel);
    button.setAttribute("aria-pressed", String(open));
    button.textContent = labelFor(open);
    document.documentElement.classList.toggle("preview-fullscreen-fallback", open && document.fullscreenElement !== panel);
  }

  controls.forEach((button) => {
    const panel = document.getElementById(button.dataset.previewTarget);
    if (!panel) return;

    const close = async () => {
      if (document.fullscreenElement === panel && document.exitFullscreen) {
        try {
          await document.exitFullscreen();
        } catch (_) {
          panel.classList.remove("is-fullscreen");
        }
      } else {
        panel.classList.remove("is-fullscreen");
      }
      sync(panel, button);
      button.focus();
      window.dispatchEvent(new Event("resize"));
    };

    button.addEventListener("click", async () => {
      if (isOpen(panel)) {
        await close();
        return;
      }
      if (panel.requestFullscreen) {
        try {
          await panel.requestFullscreen();
          return;
        } catch (_) {
          // The fixed-position mode below also works when fullscreen API access is blocked.
        }
      }
      panel.classList.add("is-fullscreen");
      sync(panel, button);
      window.dispatchEvent(new Event("resize"));
    });

    document.addEventListener("fullscreenchange", () => {
      sync(panel, button);
      if (!isOpen(panel)) window.dispatchEvent(new Event("resize"));
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && panel.classList.contains("is-fullscreen")) {
        event.preventDefault();
        close();
      }
    });

    sync(panel, button);
  });
})();
