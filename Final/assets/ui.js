// Shared UI enhancements (effects only; no theme color changes)
// - Breadcrumbs
// - Table row selection
// - Sticky table header shadow on scroll
// - Form submit loading state + invalid field highlighting/shake
// - Tooltip helper for "Format:" hints

(function () {
  "use strict";

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }
  function $all(sel, root) {
    return Array.from((root || document).querySelectorAll(sel));
  }

  function addBreadcrumbs() {
    const header = $(".section-header");
    const h2 = header ? $("h2", header) : null;
    if (!header || !h2) return;

    // Avoid duplicating
    if ($(".breadcrumb", header.parentElement || header)) return;

    const path = (location.pathname || "").toLowerCase();

    const crumbs = [{ label: "Home", href: "../homepage.php" }];
    const push = (label, href) => crumbs.push({ label, href });

    if (path.includes("/schools/")) push("Schools", "../schools/schools.php");
    else if (path.includes("/departments/")) push("Departments", "../departments/chooseSchool.php");
    else if (path.includes("/programs/")) push("Programs", "../programs/programs.php");
    else if (path.includes("/students/")) push("Students", "../students/students.php");
    else if (path.includes("/users/")) push("Users", "../users/users.php");

    // Current page title (no link)
    const title = (h2.textContent || "").trim();
    if (title) crumbs.push({ label: title, href: null });

    const nav = document.createElement("div");
    nav.className = "breadcrumb";

    crumbs.forEach((c, idx) => {
      if (idx > 0) {
        const sep = document.createElement("span");
        sep.className = "sep";
        sep.textContent = "/";
        nav.appendChild(sep);
      }
      if (c.href) {
        const a = document.createElement("a");
        a.href = c.href;
        a.textContent = c.label;
        nav.appendChild(a);
      } else {
        const span = document.createElement("span");
        span.textContent = c.label;
        nav.appendChild(span);
      }
    });

    // Put it under the header (after section-header)
    header.insertAdjacentElement("afterend", nav);
  }

  function enableTableRowSelection() {
    $all("table").forEach((table) => {
      const body = $("tbody", table);
      if (!body) return;

      // Mark table as clickable for cursor styling
      table.classList.add("table-row-clickable");

      body.addEventListener("click", (e) => {
        const tr = e.target && e.target.closest ? e.target.closest("tr") : null;
        if (!tr || tr.parentElement !== body) return;

        // Ignore clicks on buttons/inputs that should not select rows
        const tag = (e.target && e.target.tagName ? e.target.tagName.toLowerCase() : "");
        if (tag === "input" || tag === "select" || tag === "textarea") return;

        $all("tr.is-selected", body).forEach((r) => r.classList.remove("is-selected"));
        tr.classList.add("is-selected");
      });
    });
  }

  function enableStickyHeaderShadow() {
    // Works best when table is inside .table-wrap (overflow container)
    $all(".table-wrap").forEach((wrap) => {
      const table = $("table", wrap);
      if (!table) return;

      const onScroll = () => {
        if (wrap.scrollTop > 0) table.classList.add("is-scrolled");
        else table.classList.remove("is-scrolled");
      };
      wrap.addEventListener("scroll", onScroll, { passive: true });
      onScroll();
    });
  }

  function enhanceFormatHintsAsTooltips() {
    // Convert any "Format:" helper span into a tooltip icon while keeping the text.
    $all("span.error-msg").forEach((span) => {
      const txt = (span.textContent || "").trim();
      if (!txt.toLowerCase().startsWith("format:")) return;
      if (span.dataset.tooltipEnhanced === "1") return;
      span.dataset.tooltipEnhanced = "1";

      const tip = document.createElement("span");
      tip.className = "tip";
      // Keep original text visible, add icon + tooltip
      const textNode = document.createElement("span");
      textNode.textContent = txt;

      const icon = document.createElement("span");
      icon.className = "tip-icon";
      icon.setAttribute("tabindex", "0");
      icon.setAttribute("aria-label", "Format help");
      icon.textContent = "?";

      const content = document.createElement("span");
      content.className = "tip-content";
      content.textContent = txt;

      tip.appendChild(textNode);
      tip.appendChild(icon);
      tip.appendChild(content);

      span.textContent = "";
      span.appendChild(tip);
    });
  }

  function enhanceForms() {
    $all("form").forEach((form) => {
      // On submit: add loading state to the submit button
      form.addEventListener("submit", () => {
        const btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (btn) {
          btn.classList.add("is-loading");
          try {
            btn.setAttribute("aria-disabled", "true");
            btn.disabled = true;
          } catch (_) {}
        }
      });

      // Mark fields with visible error messages as invalid and shake once.
      // Works with the existing pattern: input/select + <span class="error-msg">...</span>
      $all(".form-row", form).forEach((row) => {
        const err = $(".error-msg", row);
        const msg = err ? (err.textContent || "").trim() : "";
        if (!msg) return;
        // Ignore "Format:" helper text
        if (msg.toLowerCase().startsWith("format:")) return;

        const field = row.querySelector("input, select, textarea");
        if (!field) return;
        field.classList.add("is-invalid");
        field.classList.add("shake");
        setTimeout(() => field.classList.remove("shake"), 260);
      });
    });
  }

  function enhanceSelects() {
    // Wrap selects with a decorative container so we can add arrow + focus effects in CSS
    $all("select").forEach((sel) => {
      // Skip if already wrapped
      const parent = sel.parentElement;
      if (!parent) return;
      if (parent.classList && parent.classList.contains("select-wrap")) return;

      const wrap = document.createElement("span");
      wrap.className = "select-wrap";
      parent.insertBefore(wrap, sel);
      wrap.appendChild(sel);
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    addBreadcrumbs();
    enableTableRowSelection();
    enableStickyHeaderShadow();
    enhanceFormatHintsAsTooltips();
    enhanceForms();
    enhanceSelects();
  });
})();
