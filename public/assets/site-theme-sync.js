(() => {
  if (!("BroadcastChannel" in window)) return;
  const channel = new BroadcastChannel("bcs-site-settings");
  channel.addEventListener("message", ({ data }) => {
    if (typeof data?.activeTheme === "string") document.documentElement.dataset.theme = data.activeTheme;
    if (typeof data?.colorMode === "string") document.documentElement.dataset.colorMode = data.colorMode;
  });
})();
