import axios from "axios";
import * as bootstrap from "bootstrap";
import "bootstrap/dist/css/bootstrap.rtl.min.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import "../css/admin.css";

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
if (csrfToken) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = csrfToken;
}
window.bootstrap = bootstrap;

const sidebarShell = document.getElementById("adminShell");
const sidebarKey = "raoah-admin-sidebar-collapsed";
const desktopQuery = window.matchMedia("(min-width: 1200px)");

const persistSidebar = () => {
    if (!sidebarShell || !desktopQuery.matches) {
        return;
    }

    localStorage.setItem(
        sidebarKey,
        sidebarShell.classList.contains("sidebar-collapsed") ? "1" : "0",
    );
};

const closeMobileSidebar = () => {
    sidebarShell?.classList.remove("sidebar-open");
};

const syncSidebarMode = () => {
    if (!sidebarShell) {
        return;
    }

    if (desktopQuery.matches) {
        sidebarShell.classList.remove("sidebar-open");
        if (localStorage.getItem(sidebarKey) === "1") {
            sidebarShell.classList.add("sidebar-collapsed");
        }
        return;
    }

    sidebarShell.classList.remove("sidebar-collapsed");
};

syncSidebarMode();
desktopQuery.addEventListener("change", syncSidebarMode);

document.querySelector("[data-sidebar-toggle]")?.addEventListener("click", () => {
    if (!sidebarShell) {
        return;
    }

    if (!desktopQuery.matches) {
        sidebarShell.classList.toggle("sidebar-open");
        return;
    }

    sidebarShell.classList.toggle("sidebar-collapsed");
    persistSidebar();
});

document.querySelector("[data-sidebar-backdrop]")?.addEventListener("click", closeMobileSidebar);
document.querySelectorAll(".admin-sidebar .nav-link").forEach((link) => {
    link.addEventListener("click", () => {
        if (!desktopQuery.matches) {
            closeMobileSidebar();
        }
    });
});

document.addEventListener("change", (event) => {
    const input = event.target.closest("[data-image-preview]");
    if (!(input instanceof HTMLInputElement)) {
        return;
    }
    const preview = document.querySelector(input.dataset.imagePreview);
    const file = input.files?.[0];
    if (!preview || !file) {
        return;
    }
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
});

document.addEventListener("keydown", (event) => {
    if (event.key !== "/" || event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }
    const tag = document.activeElement?.tagName;
    if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") {
        return;
    }
    const search = document.querySelector(".admin-search input");
    if (!search) {
        return;
    }
    event.preventDefault();
    search.focus();
});

const syncColorLabel = (input) => {
    const label = input.dataset.colorSync ? document.querySelector(input.dataset.colorSync) : null;
    if (label) {
        label.textContent = input.value;
    }

    const preview = input.dataset.colorPreview ? document.querySelector(input.dataset.colorPreview) : null;
    if (preview) {
        preview.style.background = input.value;
    }
};

document.querySelectorAll("[data-color-sync]").forEach(syncColorLabel);
document.addEventListener("input", (event) => {
    const input = event.target.closest("[data-color-sync]");
    if (input) {
        syncColorLabel(input);
    }
});

const syncHomeSectionBackgroundToggle = (checkbox) => {
    const target = document.querySelector(checkbox.dataset.homeSectionBgToggle);
    if (!target) {
        return;
    }
    const useDefault = checkbox.checked;
    target.disabled = useDefault;
    target.classList.toggle("opacity-50", useDefault);
};

const bindHomeSectionBackgroundToggle = () => {
    document.querySelectorAll("[data-home-section-bg-toggle]").forEach((checkbox) => {
        if (checkbox.dataset.homeSectionBgBound === "1") {
            return;
        }
        checkbox.dataset.homeSectionBgBound = "1";
        syncHomeSectionBackgroundToggle(checkbox);
        checkbox.addEventListener("change", () => syncHomeSectionBackgroundToggle(checkbox));
    });
};

bindHomeSectionBackgroundToggle();
window.addEventListener("admin:content-ready", bindHomeSectionBackgroundToggle);

const syncLinkPanels = (select) => {
    const value = select.value;
    document.querySelectorAll("[data-link-panel]").forEach((panel) => {
        panel.hidden = panel.dataset.linkPanel !== value;
    });
};

document.querySelectorAll("[data-link-type]").forEach(syncLinkPanels);
document.addEventListener("change", (event) => {
    const select = event.target.closest("[data-link-type]");
    if (select) {
        syncLinkPanels(select);
    }
});

document.addEventListener("input", (event) => {
    const input = event.target.closest("[data-picker-search]");
    if (!input) {
        return;
    }
    const root = document.querySelector(input.dataset.pickerSearch);
    if (!root) {
        return;
    }
    const term = input.value.trim().toLowerCase();
    root.querySelectorAll("[data-picker-text]").forEach((item) => {
        const text = (item.dataset.pickerText || "").toLowerCase();
        item.hidden = term !== "" && !text.includes(term);
    });
});

const setText = (el, value) => {
    if (el) {
        el.textContent = value || "";
    }
};

const fillDetailModal = (data) => {
    const modal = document.getElementById("adminDetailModal");
    if (!modal || !data) {
        return;
    }

    setText(modal.querySelector("[data-detail-title]"), data.title);
    const hero = modal.querySelector("[data-detail-hero]");
    const image = modal.querySelector("[data-detail-image]");
    if (hero && image) {
        if (data.image) {
            image.src = data.image;
            hero.hidden = false;
        } else {
            image.removeAttribute("src");
            hero.hidden = true;
        }
    }

    const badges = modal.querySelector("[data-detail-badges]");
    if (badges) {
        badges.replaceChildren();
        (data.badges || []).forEach((label) => {
            const badge = document.createElement("span");
            badge.className = "badge badge-soft";
            badge.textContent = label;
            badges.append(badge);
        });
    }

    const fields = modal.querySelector("[data-detail-fields]");
    if (fields) {
        fields.replaceChildren();
        (data.fields || []).forEach((row) => {
            const dt = document.createElement("dt");
            dt.textContent = row.label || "";
            const dd = document.createElement("dd");
            if (row.map_url) {
                const link = document.createElement("a");
                link.href = row.map_url;
                link.target = "_blank";
                link.rel = "noopener noreferrer";
                link.className = "map-link";
                const icon = document.createElement("i");
                icon.className = "bi bi-geo-alt-fill";
                link.append(icon, document.createTextNode(row.value || "عرض على الخريطة"));
                dd.append(link);
            } else {
                dd.textContent = row.value || "—";
            }
            if (row.label === "اللون" && row.value) {
                const dot = document.createElement("span");
                dot.className = "color-dot";
                dot.style.background = row.value;
                dd.prepend(dot);
            }
            fields.append(dt, dd);
        });
    }

    const blocks = modal.querySelector("[data-detail-blocks]");
    if (blocks) {
        blocks.replaceChildren();
        (data.blocks || []).forEach((block) => {
            const wrap = document.createElement("div");
            wrap.className = "detail-block";
            const title = document.createElement("h6");
            title.textContent = block.label || "";
            wrap.append(title);
            if (Array.isArray(block.list) && block.list.length) {
                const list = document.createElement("ul");
                block.list.forEach((item) => {
                    const li = document.createElement("li");
                    li.textContent = item;
                    list.append(li);
                });
                wrap.append(list);
            } else if (Array.isArray(block.cards) && block.cards.length) {
                const grid = document.createElement("div");
                grid.className = "detail-cards";
                block.cards.forEach((card) => {
                    const article = document.createElement("article");
                    article.className = "detail-card";
                    const head = document.createElement("div");
                    head.className = "detail-card-head";
                    const title = document.createElement("strong");
                    title.textContent = card.title || "";
                    head.append(title);
                    if (card.badge) {
                        const badge = document.createElement("span");
                        badge.className = "badge badge-soft";
                        badge.textContent = card.badge;
                        head.append(badge);
                    }
                    article.append(head);
                    if (card.text) {
                        const text = document.createElement("p");
                        text.textContent = card.text;
                        article.append(text);
                    }
                    if (card.meta) {
                        const meta = document.createElement("small");
                        meta.textContent = card.meta;
                        article.append(meta);
                    }
                    if (card.map_url) {
                        const link = document.createElement("a");
                        link.href = card.map_url;
                        link.target = "_blank";
                        link.rel = "noopener noreferrer";
                        link.className = "map-link";
                        const icon = document.createElement("i");
                        icon.className = "bi bi-geo-alt-fill";
                        link.append(icon, document.createTextNode("عرض الموقع على الخريطة"));
                        article.append(link);
                    }
                    grid.append(article);
                });
                wrap.append(grid);
            } else if (block.text) {
                const p = document.createElement("p");
                p.textContent = block.text;
                wrap.append(p);
            }
            blocks.append(wrap);
        });
    }

    const edit = modal.querySelector("[data-detail-edit]");
    if (edit) {
        edit.href = data.edit_url || "#";
        edit.hidden = !data.edit_url;
    }
};

document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-detail]");
    if (!button) {
        return;
    }

    let payload = {};
    try {
        payload = JSON.parse(button.getAttribute("data-detail") || "{}");
    } catch {
        payload = {};
    }
    fillDetailModal(payload);
    const modal = document.getElementById("adminDetailModal");
    if (modal) {
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
    }
});

const isModifiedClick = (event) =>
    event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;

let ajaxController = null;
let adminMutating = false;

const playFlasherFrom = (doc) => {
    const source = doc.querySelector("script.flasher-js");
    if (!source?.textContent) {
        return;
    }

    const flasher = window.flasher;
    if (!flasher || typeof flasher.render !== "function") {
        const run = document.createElement("script");
        run.className = "flasher-js";
        run.textContent = source.textContent;
        document.body.appendChild(run);
        return;
    }

    const text = source.textContent;
        const token = "options.push(";
        const start = text.lastIndexOf(token);
        if (start === -1) {
            return;
        }

        let index = start + token.length;
        while (index < text.length && /\s/.test(text[index])) {
            index += 1;
        }
        if (text[index] !== "{") {
            return;
        }

        let depth = 0;
        let end = index;
        for (; end < text.length; end += 1) {
            const char = text[end];
            if (char === "{") {
                depth += 1;
            } else if (char === "}") {
                depth -= 1;
                if (depth === 0) {
                    end += 1;
                    break;
                }
            } else if (char === '"' || char === "'") {
                const quote = char;
                end += 1;
                while (end < text.length && text[end] !== quote) {
                    if (text[end] === "\\") {
                        end += 1;
                    }
                    end += 1;
                }
            }
        }

        try {
            flasher.render(JSON.parse(text.slice(index, end)));
        } catch {
            // تجاهل إشعار تالف
        }
};

const syncSidebarActive = (url) => {
    const currentPath = new URL(url, window.location.origin).pathname.replace(/\/+$/, "") || "/";
    document.querySelectorAll(".admin-sidebar .nav-link").forEach((link) => {
        const path = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, "") || "/";
        const isDashboard = /\/admin$/.test(path);
        link.classList.toggle(
            "active",
            isDashboard ? currentPath === path : currentPath === path || currentPath.startsWith(`${path}/`),
        );
    });
};

const applyAdminDocument = (doc, url, { historyMode = "none" } = {}) => {
    const currentMain = document.querySelector(".admin-content");
    const nextMain = doc.querySelector(".admin-content");
    if (!currentMain || !nextMain) {
        window.location.assign(url);
        return false;
    }

    currentMain.innerHTML = nextMain.innerHTML;
    document.title = doc.title || document.title;

    const nextTop = doc.querySelector(".topbar-title div");
    const currentTop = document.querySelector(".topbar-title div");
    if (nextTop && currentTop) {
        currentTop.textContent = nextTop.textContent;
    }

    syncSidebarActive(url);

    const nextCsrf = doc.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    if (nextCsrf) {
        document.querySelector('meta[name="csrf-token"]')?.setAttribute("content", nextCsrf);
        document.querySelectorAll('input[name="_token"]').forEach((input) => {
            input.value = nextCsrf;
        });
        if (window.axios) {
            window.axios.defaults.headers.common["X-CSRF-TOKEN"] = nextCsrf;
        }
    }

    document.querySelectorAll("[data-color-sync]").forEach(syncColorLabel);
    document.querySelectorAll("[data-link-type]").forEach(syncLinkPanels);
    bindHomeSectionBackgroundToggle();
    document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());
    document.body.classList.remove("modal-open");
    document.body.style.removeProperty("overflow");
    document.body.style.removeProperty("padding-right");
    window.dispatchEvent(new Event("admin:content-ready"));

    if (historyMode === "push") {
        history.pushState({ ajaxAdmin: true }, "", url);
    } else if (historyMode === "replace") {
        history.replaceState({ ajaxAdmin: true }, "", url);
    }

    playFlasherFrom(doc);
    return true;
};

const swapAdminContent = async (url, { push = true, silent = false } = {}) => {
    if (adminMutating) {
        return;
    }

    const currentMain = document.querySelector(".admin-content");
    if (!currentMain) {
        window.location.assign(url);
        return;
    }

    ajaxController?.abort();
    ajaxController = new AbortController();
    if (!silent) {
        currentMain.classList.add("is-ajax-loading");
    }
    const scrollY = window.scrollY;

    try {
        const response = await fetch(url, {
            signal: ajaxController.signal,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "text/html",
            },
            credentials: "same-origin",
        });
        if (response.status === 419) {
            window.location.assign("/admin/login");
            return;
        }
        if (!response.ok) {
            window.location.assign(url);
            return;
        }

        const doc = new DOMParser().parseFromString(await response.text(), "text/html");
        if (!applyAdminDocument(doc, response.url || url, { historyMode: push ? "push" : "none" })) {
            return;
        }

        window.scrollTo(0, scrollY);
    } catch (error) {
        if (error?.name === "AbortError") {
            return;
        }
        window.location.assign(url);
    } finally {
        currentMain.classList.remove("is-ajax-loading");
    }
};

const submitAdminForm = async (form, submitter) => {
    const currentMain = document.querySelector(".admin-content");
    if (!currentMain) {
        form.submit();
        return;
    }

    adminMutating = true;
    ajaxController?.abort();
    currentMain.classList.add("is-ajax-loading");
    if (submitter) {
        submitter.disabled = true;
    }

    const formData = new FormData(form);
    if (submitter?.name && !formData.has(submitter.name)) {
        formData.append(submitter.name, submitter.value ?? "");
    }

    try {
        const response = await fetch(form.getAttribute("action") || window.location.href, {
            method: "POST",
            body: formData,
            credentials: "same-origin",
            headers: {
                Accept: "text/html",
            },
            redirect: "follow",
        });

        if (response.status === 419) {
            window.location.assign("/admin/login");
            return;
        }

        if (response.redirected && /\/login(?:\/|$|\?)/.test(new URL(response.url).pathname)) {
            window.location.assign(response.url);
            return;
        }

        const contentType = response.headers.get("content-type") || "";
        if (!contentType.includes("text/html")) {
            window.location.assign(response.url || window.location.href);
            return;
        }

        const doc = new DOMParser().parseFromString(await response.text(), "text/html");
        applyAdminDocument(doc, response.url || window.location.href, { historyMode: "replace" });
    } catch {
        form.submit();
    } finally {
        adminMutating = false;
        currentMain.classList.remove("is-ajax-loading");
        if (submitter) {
            submitter.disabled = false;
        }
    }
};

document.addEventListener("click", (event) => {
    if (isModifiedClick(event)) {
        return;
    }

    const link = event.target.closest(".simple-pager a[href], .pagination a[href]");
    if (!link || link.target === "_blank") {
        return;
    }
    const href = link.getAttribute("href");
    if (!href || href === "#") {
        return;
    }

    event.preventDefault();
    swapAdminContent(link.href);
});

document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.closest(".admin-content")) {
        return;
    }
    if (event.defaultPrevented || form.target === "_blank") {
        return;
    }

    const method = (form.getAttribute("method") || "get").toLowerCase();
    event.preventDefault();

    if (method === "get") {
        const url = new URL(form.getAttribute("action") || window.location.href, window.location.origin);
        url.search = "";
        new FormData(form).forEach((value, key) => {
            if (String(value).trim() !== "") {
                url.searchParams.append(key, String(value));
            }
        });
        swapAdminContent(url.toString());
        return;
    }

    submitAdminForm(form, event.submitter instanceof HTMLElement ? event.submitter : null);
});

window.addEventListener("popstate", () => {
    swapAdminContent(window.location.href, { push: false });
});

const currency = "\u20C1";

const formatMoney = (value) =>
    `${Number(value).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;

const refreshOrderLine = (row) => {
    const price = Number(row.dataset.price || 0);
    const qty = Number(row.querySelector("[data-order-qty-input], [data-qty-input]")?.value || 0);
    const cell = row.querySelector("[data-line-total]");
    if (cell) {
        cell.textContent = formatMoney(price * qty);
    }
};

const syncOrderEmptyState = (editor) => {
    const empty = editor.querySelector("[data-order-empty]");
    const rows = editor.querySelectorAll("[data-order-items] tbody tr");
    if (empty) {
        empty.hidden = rows.length > 0;
    }
};

const setOrderChipActive = (buttons, selected) => {
    buttons.forEach((btn) => {
        const on = btn === selected;
        btn.classList.toggle("btn-brand", on);
        btn.classList.toggle("btn-outline-success", !on);
    });
};

const filterOrderCatalog = (catalog) => {
    const term = (catalog.querySelector("[data-order-product-search]")?.value || "").trim().toLowerCase();
    const activeRoot = catalog.querySelector("[data-order-cat-root].btn-brand");
    const activeChild = catalog.querySelector("[data-order-children] [data-order-cat].btn-brand");
    const rootId = activeRoot?.dataset.orderCat || "";
    const childId = activeChild?.dataset.orderCat || "";
    let visible = 0;

    catalog.querySelectorAll("[data-order-product-card]").forEach((card) => {
        const name = (card.dataset.name || "").toLowerCase();
        const matchesSearch = term === "" || name.includes(term);
        let matchesCat = true;
        if (childId) {
            matchesCat = String(card.dataset.categoryId || "") === childId;
        } else if (rootId) {
            matchesCat =
                String(card.dataset.categoryRoot || "") === rootId ||
                String(card.dataset.categoryId || "") === rootId;
        }
        const show = matchesSearch && matchesCat;
        card.hidden = !show;
        if (show) {
            visible += 1;
        }
    });

    const empty = catalog.querySelector("[data-order-catalog-empty]");
    if (empty) {
        empty.hidden = visible > 0;
    }
};

const showOrderChildCats = (catalog, rootId) => {
    const row = catalog.querySelector("[data-order-children]");
    if (!row) {
        return;
    }
    let any = false;
    row.querySelectorAll("[data-order-cat]").forEach((btn) => {
        const match = rootId !== "" && String(btn.dataset.orderParent || "") === rootId;
        btn.hidden = !match;
        btn.classList.add("btn-outline-success");
        btn.classList.remove("btn-brand");
        if (match) {
            any = true;
        }
    });
    row.hidden = !any;
};

const productFromCard = (card) => ({
    id: card?.dataset.id,
    name: card?.dataset.name,
    price: card?.dataset.price,
    stock: card?.dataset.stock,
});

const orderQtyFor = (editor, productId) => {
    const input = editor?.querySelector(
        `tr[data-product-id="${productId}"] [data-order-qty-input], tr[data-product-id="${productId}"] [data-qty-input]`,
    );
    return Number(input?.value || 0);
};

const syncCardQtyDisplays = (editor) => {
    editor?.querySelectorAll("[data-order-product-card]").forEach((card) => {
        const qty = orderQtyFor(editor, card.dataset.id);
        const stock = Number(card.dataset.stock || 0);
        const label = card.querySelector("[data-order-card-qty]");
        const minus = card.querySelector("[data-order-card-minus]");
        const plus = card.querySelector("[data-order-card-plus]");
        if (label) {
            label.textContent = String(qty);
        }
        card.classList.toggle("is-in-order", qty > 0);
        if (minus) {
            minus.disabled = qty < 1;
        }
        if (plus) {
            plus.disabled = stock < 1 || qty >= stock;
        }
    });
};

const addProductToOrder = (editor, product, qty, options = {}) => {
    const template = document.getElementById("order-item-row");
    const body = editor?.querySelector("[data-order-items] tbody");
    if (!editor || !template || !body || !product?.id) {
        return false;
    }

    const productId = String(product.id);
    const name = product.name || "";
    const price = Number(product.price || 0);
    const stock = Number(product.stock || 0);
    const amount = Math.max(1, Number(qty || 1));
    const existing = body.querySelector(`tr[data-product-id="${productId}"]`);

    if (stock < 1 && !existing) {
        window.alert("هذا المنتج غير متوفر حالياً.");
        return false;
    }

    if (existing) {
        const input = existing.querySelector("[data-order-qty-input], [data-qty-input]");
        const next = Math.min(Math.max(1, stock), Number(input.value || 0) + amount);
        input.value = String(next);
        refreshOrderLine(existing);
        if (options.scroll) {
            existing.scrollIntoView({ behavior: "smooth", block: "nearest" });
        }
        syncCardQtyDisplays(editor);
        return true;
    }

    const row = template.content.firstElementChild.cloneNode(true);
    row.dataset.productId = productId;
    row.dataset.price = String(price);
    row.querySelector("[data-name]").textContent = name;
    const idInput = row.querySelector("[data-id-input]");
    idInput.name = `items[${productId}][product_id]`;
    idInput.value = productId;
    row.querySelector("[data-unit]").textContent = formatMoney(price);
    const rowQty = row.querySelector("[data-qty-input]");
    rowQty.name = `items[${productId}][quantity]`;
    rowQty.max = String(Math.max(1, stock));
    rowQty.value = String(Math.min(amount, Math.max(1, stock)));
    rowQty.setAttribute("data-order-qty-input", "");
    body.append(row);
    refreshOrderLine(row);
    syncOrderEmptyState(editor);
    if (options.scroll) {
        row.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
    syncCardQtyDisplays(editor);
    return true;
};

const decreaseProductFromOrder = (editor, productId) => {
    const body = editor?.querySelector("[data-order-items] tbody");
    const existing = body?.querySelector(`tr[data-product-id="${productId}"]`);
    if (!editor || !existing) {
        return false;
    }

    const input = existing.querySelector("[data-order-qty-input], [data-qty-input]");
    const next = Number(input?.value || 0) - 1;
    if (next < 1) {
        const rows = body.querySelectorAll("tr");
        if (rows.length <= 1) {
            window.alert("يجب أن يبقى منتج واحد على الأقل في الطلب.");
            return false;
        }
        existing.remove();
        syncOrderEmptyState(editor);
        syncCardQtyDisplays(editor);
        return true;
    }

    input.value = String(next);
    refreshOrderLine(existing);
    syncCardQtyDisplays(editor);
    return true;
};

const openOrderProductModal = (card) => {
    const modal = document.getElementById("orderProductModal");
    if (!modal) {
        return;
    }

    modal.dataset.id = card.dataset.id || "";
    modal.dataset.name = card.dataset.name || "";
    modal.dataset.price = card.dataset.price || "0";
    modal.dataset.stock = card.dataset.stock || "0";

    setText(modal.querySelector("[data-pick-title]"), card.dataset.name || "تفاصيل المنتج");
    const hero = modal.querySelector("[data-pick-hero]");
    const image = modal.querySelector("[data-pick-image]");
    if (hero && image) {
        if (card.dataset.image) {
            image.src = card.dataset.image;
            image.alt = card.dataset.name || "";
            hero.hidden = false;
        } else {
            image.removeAttribute("src");
            hero.hidden = true;
        }
    }

    const badges = modal.querySelector("[data-pick-badges]");
    if (badges) {
        badges.replaceChildren();
        [card.dataset.categoryName, card.dataset.sku ? `الرمز: ${card.dataset.sku}` : ""]
            .filter(Boolean)
            .forEach((label) => {
                const badge = document.createElement("span");
                badge.className = "badge badge-soft";
                badge.textContent = label;
                badges.append(badge);
            });
    }

    const description = modal.querySelector("[data-pick-description]");
    if (description) {
        const text = (card.dataset.description || "").trim();
        description.textContent = text;
        description.hidden = text === "";
    }

    const price = Number(card.dataset.price || 0);
    const original = Number(card.dataset.originalPrice || 0);
    const priceEl = modal.querySelector("[data-pick-price]");
    if (priceEl) {
        priceEl.textContent =
            original > price
                ? `${formatMoney(price)}  (بدلاً من ${formatMoney(original)})`
                : formatMoney(price);
    }
    setText(modal.querySelector("[data-pick-stock]"), card.dataset.stock || "0");

    const qty = modal.querySelector("[data-pick-qty]");
    const stock = Number(card.dataset.stock || 0);
    if (qty) {
        qty.value = "1";
        qty.max = String(Math.max(1, stock));
        qty.disabled = stock < 1;
    }
    const addBtn = modal.querySelector("[data-pick-add]");
    if (addBtn) {
        addBtn.disabled = stock < 1;
    }

    window.bootstrap.Modal.getOrCreateInstance(modal).show();
};

document.addEventListener("click", (event) => {
    const catBtn = event.target.closest("[data-order-cat]");
    if (catBtn) {
        event.preventDefault();
        const catalog = catBtn.closest("[data-order-catalog]");
        if (!catalog) {
            return;
        }
        if (catBtn.hasAttribute("data-order-cat-root")) {
            setOrderChipActive(catalog.querySelectorAll("[data-order-cat-root]"), catBtn);
            showOrderChildCats(catalog, catBtn.dataset.orderCat || "");
        } else {
            const already = catBtn.classList.contains("btn-brand");
            setOrderChipActive(
                catalog.querySelectorAll("[data-order-children] [data-order-cat]"),
                already ? null : catBtn,
            );
        }
        filterOrderCatalog(catalog);
        return;
    }

    const plus = event.target.closest("[data-order-card-plus]");
    if (plus) {
        event.preventDefault();
        const card = plus.closest("[data-order-product-card]");
        const editor = plus.closest("[data-order-editor]");
        addProductToOrder(editor, productFromCard(card), 1);
        return;
    }

    const minus = event.target.closest("[data-order-card-minus]");
    if (minus) {
        event.preventDefault();
        const card = minus.closest("[data-order-product-card]");
        const editor = minus.closest("[data-order-editor]");
        decreaseProductFromOrder(editor, card?.dataset.id);
        return;
    }

    const openCard = event.target.closest("[data-order-product-open]");
    if (openCard) {
        event.preventDefault();
        openOrderProductModal(openCard.closest("[data-order-product-card]"));
        return;
    }

    const pickAdd = event.target.closest("[data-pick-add]");
    if (pickAdd) {
        event.preventDefault();
        const modal = pickAdd.closest("#orderProductModal");
        const editor = document.querySelector("[data-order-editor]");
        const qty = Number(modal?.querySelector("[data-pick-qty]")?.value || 1);
        const added = addProductToOrder(editor, modal?.dataset || {}, qty, { scroll: true });
        if (added && modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        }
        return;
    }

    const addBtn = event.target.closest("[data-order-add-item]");
    if (addBtn) {
        event.preventDefault();
        const editor = addBtn.closest("[data-order-editor]");
        const select = editor?.querySelector("[data-order-product]");
        const qtyInput = editor?.querySelector("[data-order-add-qty]");
        const option = select?.selectedOptions?.[0];
        if (!editor || !select || !option || !option.value) {
            return;
        }
        addProductToOrder(
            editor,
            {
                id: option.value,
                name: option.dataset.name || option.textContent.trim(),
                price: option.dataset.price,
                stock: option.dataset.stock,
            },
            qtyInput?.value,
        );
        select.value = "";
        if (qtyInput) {
            qtyInput.value = "1";
        }
        return;
    }

    const removeBtn = event.target.closest("[data-order-remove-item]");
    if (!removeBtn) {
        return;
    }
    event.preventDefault();
    const editor = removeBtn.closest("[data-order-editor]");
    const rows = editor?.querySelectorAll("[data-order-items] tbody tr") || [];
    if (rows.length <= 1) {
        window.alert("يجب أن يبقى منتج واحد على الأقل في الطلب.");
        return;
    }
    removeBtn.closest("tr")?.remove();
    if (editor) {
        syncOrderEmptyState(editor);
        syncCardQtyDisplays(editor);
    }
});

document.addEventListener("input", (event) => {
    const qty = event.target.closest("[data-order-qty-input], [data-qty-input]");
    if (qty) {
        refreshOrderLine(qty.closest("tr"));
        syncCardQtyDisplays(qty.closest("[data-order-editor]"));
    }

    const search = event.target.closest("[data-order-product-search]");
    if (!search) {
        return;
    }
    const catalog = search.closest("[data-order-catalog]");
    if (catalog) {
        filterOrderCatalog(catalog);
        return;
    }
    const editor = search.closest("[data-order-editor]");
    const select = editor?.querySelector("[data-order-product]");
    if (!select) {
        return;
    }
    const term = search.value.trim().toLowerCase();
    [...select.options].forEach((option, index) => {
        if (index === 0) {
            return;
        }
        const text = (option.dataset.pickerText || option.textContent || "").toLowerCase();
        option.hidden = term !== "" && !text.includes(term);
    });
});

const setFormField = (form, name, value) => {
    const el = form.querySelector(`[name="${name}"]`);
    if (!el) {
        return;
    }
    if (el.type === "checkbox") {
        el.checked = value === true || value === "1" || value === 1;
        return;
    }
    el.value = value ?? "";
};

const clearFormErrors = (form) => {
    form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
    form.querySelectorAll(".invalid-feedback").forEach((el) => el.remove());
};

const setDeliveryFormMode = (form, id) => {
    const method = form.querySelector("[data-http-method]");
    const editing = form.querySelector("[data-editing-id]");
    const scope = form.closest(".modal") || form;
    const title = scope.querySelector("[data-rule-title], [data-perk-title], [data-phrase-title], [data-smart-title], [data-trending-title]");
    if (id) {
        form.action = `${String(form.dataset.updateBase || "").replace(/\/$/, "")}/${id}`;
        if (method) {
            method.value = "PUT";
        }
        if (editing) {
            editing.value = String(id);
        }
        if (title) {
            title.textContent = form.dataset.titleEdit || title.textContent;
        }
        return;
    }
    form.action = form.dataset.store || form.action;
    if (method) {
        method.value = "POST";
    }
    if (editing) {
        editing.value = "";
    }
    if (title) {
        title.textContent = form.dataset.titleCreate || title.textContent;
    }
};

const syncRulePricingUI = (root = document) => {
    root.querySelectorAll("[data-pricing-type]").forEach((select) => {
        const form = select.closest("form");
        if (!form) {
            return;
        }
        const type = select.value;
        const amountWrap = form.querySelector("[data-amount-wrap]");
        const modeWrap = form.querySelector("[data-mode-wrap]");
        if (amountWrap) {
            amountWrap.hidden = type === "free";
        }
        if (modeWrap) {
            modeWrap.hidden = type !== "per_km";
        }
    });
};

const syncPerkRewardUI = (root = document) => {
    root.querySelectorAll("[data-perk-reward]").forEach((select) => {
        const form = select.closest("form");
        const wrap = form?.querySelector("[data-perk-value-wrap]");
        if (wrap) {
            wrap.hidden = select.value === "free";
        }
    });
};

const resetRuleForm = (form) => {
    if (!form) {
        return;
    }
    clearFormErrors(form);
    setDeliveryFormMode(form, null);
    setFormField(form, "name", "");
    setFormField(form, "min_km", "0");
    setFormField(form, "max_km", "");
    setFormField(form, "pricing_type", "free");
    setFormField(form, "amount", "0");
    setFormField(form, "per_km_mode", "entire");
    setFormField(form, "sort_order", "0");
    setFormField(form, "is_active", "1");
    setFormField(form, "note", "");
    setFormField(form, "note_enabled", "0");
    syncRulePricingUI(form);
};

const fillRuleForm = (form, data) => {
    if (!form) {
        return;
    }
    clearFormErrors(form);
    setDeliveryFormMode(form, data.id);
    setFormField(form, "name", data.name || "");
    setFormField(form, "min_km", data.minKm ?? "0");
    setFormField(form, "max_km", data.maxKm ?? "");
    setFormField(form, "pricing_type", data.pricingType || "free");
    setFormField(form, "amount", data.amount ?? "0");
    setFormField(form, "per_km_mode", data.perKmMode || "entire");
    setFormField(form, "sort_order", data.sortOrder ?? "0");
    setFormField(form, "is_active", data.isActive);
    setFormField(form, "note", data.note || "");
    setFormField(form, "note_enabled", data.noteEnabled);
    syncRulePricingUI(form);
};

const resetPerkForm = (form) => {
    if (!form) {
        return;
    }
    clearFormErrors(form);
    setDeliveryFormMode(form, null);
    setFormField(form, "name", "");
    setFormField(form, "trigger_type", "min_orders");
    setFormField(form, "min_orders", "4");
    setFormField(form, "reward_type", "free");
    setFormField(form, "reward_value", "0");
    setFormField(form, "sort_order", "0");
    setFormField(form, "is_active", "1");
    syncPerkRewardUI(form);
};

const fillPerkForm = (form, data) => {
    if (!form) {
        return;
    }
    clearFormErrors(form);
    setDeliveryFormMode(form, data.id);
    setFormField(form, "name", data.name || "");
    setFormField(form, "trigger_type", data.triggerType || "min_orders");
    setFormField(form, "min_orders", data.minOrders ?? "4");
    setFormField(form, "reward_type", data.rewardType || "free");
    setFormField(form, "reward_value", data.rewardValue ?? "0");
    setFormField(form, "sort_order", data.sortOrder ?? "0");
    setFormField(form, "is_active", data.isActive);
    syncPerkRewardUI(form);
};

const fillSlotForm = (form, data = {}) => {
    if (!form) {
        return;
    }
    const id = data.id || data.editingId || "";
    const editing = form.querySelector("[data-editing-id]");
    if (editing) {
        editing.value = id;
    }
    const base = form.getAttribute("data-update-base") || "";
    if (id && base) {
        form.action = `${base.replace(/\/$/, "")}/${id}`;
    }
    const weekday = form.querySelector("[data-slot-weekday]");
    const start = form.querySelector("[data-slot-start]");
    const end = form.querySelector("[data-slot-end]");
    const sort = form.querySelector("[data-slot-sort]");
    const active = form.querySelector("[data-slot-active]");
    if (weekday) {
        weekday.value = String(data.weekday ?? "0");
    }
    if (start) {
        start.value = data.startTime || "10:00";
    }
    if (end) {
        end.value = data.endTime || "12:00";
    }
    if (sort) {
        sort.value = data.sortOrder ?? "0";
    }
    if (active) {
        active.checked = String(data.isActive ?? "1") === "1";
    }
    const interval = form.querySelector("[data-slot-interval]");
    if (interval) {
        interval.value = String(data.intervalMinutes ?? "15");
    }
};

const bindDeliveryPage = () => {
    syncRulePricingUI();
    syncPerkRewardUI();
    ["deliveryRuleModal", "deliveryPerkModal", "deliverySlotModal", "pickupSlotModal"].forEach((id) => {
        const modal = document.getElementById(id);
        if (modal?.dataset.open === "1") {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });
};

document.addEventListener("change", (event) => {
    if (event.target.closest("[data-pricing-type]")) {
        syncRulePricingUI();
    }
    if (event.target.closest("[data-perk-reward]")) {
        syncPerkRewardUI();
    }
});

document.addEventListener("click", (event) => {
    if (!event.target.closest("#delivery_coords_apply")) {
        return;
    }
    const raw = (document.getElementById("delivery_coords_paste")?.value || "").trim();
    const match = raw.match(/(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)/);
    if (!match) {
        window.alert("الصق الإحداثيات بهذا الشكل: 24.7136, 46.6753");
        return;
    }
    const lat = document.getElementById("delivery_store_lat");
    const lng = document.getElementById("delivery_store_lng");
    if (lat) {
        lat.value = match[1];
    }
    if (lng) {
        lng.value = match[2];
    }
});

document.addEventListener("show.bs.modal", (event) => {
    const modal = event.target;
    const trigger = event.relatedTarget;
    if (!(modal instanceof HTMLElement) || !(trigger instanceof HTMLElement)) {
        return;
    }
    if (modal.id === "deliveryRuleModal") {
        const form = modal.querySelector("#deliveryRuleForm");
        if (trigger.hasAttribute("data-delivery-rule-edit")) {
            fillRuleForm(form, trigger.dataset);
        } else if (trigger.hasAttribute("data-delivery-rule-create")) {
            resetRuleForm(form);
        }
        requestAnimationFrame(() => {
            const body = modal.querySelector(".modal-body");
            if (body) {
                body.scrollTop = 0;
            }
        });
        return;
    }
    if (modal.id === "deliveryPerkModal") {
        const form = modal.querySelector("#deliveryPerkForm");
        if (trigger.hasAttribute("data-delivery-perk-edit")) {
            fillPerkForm(form, trigger.dataset);
        } else if (trigger.hasAttribute("data-delivery-perk-create")) {
            resetPerkForm(form);
        }
        return;
    }
    if (modal.id === "deliverySlotModal") {
        const form = modal.querySelector("#deliverySlotForm");
        if (trigger.hasAttribute("data-delivery-slot-edit")) {
            fillSlotForm(form, trigger.dataset);
        }
        return;
    }
    if (modal.id === "pickupSlotModal") {
        const form = modal.querySelector("#pickupSlotForm");
        if (trigger.hasAttribute("data-pickup-slot-edit")) {
            fillSlotForm(form, trigger.dataset);
        }
    }
});

bindDeliveryPage();
window.addEventListener("admin:content-ready", bindDeliveryPage);

const escLive = (value) =>
    String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;");

const livePages = () => {
    const path = window.location.pathname.replace(/\/+$/, "");
    return (
        path.endsWith("/admin") ||
        path.includes("/admin/orders") ||
        path.includes("/admin/couriers")
    );
};

const liveBusy = () => {
    if (adminMutating || document.querySelector(".modal.show, .fl-container, .fl-wrapper")) {
        return true;
    }
    const active = document.activeElement;
    return Boolean(active && ["INPUT", "TEXTAREA", "SELECT"].includes(active.tagName));
};

const renderLiveList = (events) => {
    const list = document.querySelector("[data-live-list]");
    if (!list) {
        return;
    }
    if (!events.length) {
        list.innerHTML = '<p class="text-muted small mb-0 p-2">لا توجد تحديثات بعد.</p>';
        return;
    }
    list.innerHTML = events
        .map(
            (event) =>
                `<div class="live-bell-item"><strong>${escLive(event.title)}</strong><span>${escLive(event.body)}</span><small>${escLive(event.created_label || "")}</small></div>`,
        )
        .join("");
};

const setLiveCount = (count) => {
    const badge = document.querySelector("[data-live-count]");
    if (!badge) {
        return;
    }
    if (count > 0) {
        badge.hidden = false;
        badge.textContent = count > 9 ? "9+" : String(count);
    } else {
        badge.hidden = true;
    }
};

const pushLiveToast = (event) => {
    const stack = document.querySelector("[data-live-toasts]");
    if (!stack) {
        return;
    }
    const toast = document.createElement("div");
    toast.className = "live-toast";
    toast.innerHTML = `<strong>${escLive(event.title)}</strong><span>${escLive(event.body)}</span>`;
    stack.prepend(toast);
    setTimeout(() => toast.classList.add("is-out"), 4200);
    setTimeout(() => toast.remove(), 5000);
};

let liveStamp = null;
let liveLatestId = 0;

const pollLive = async () => {
    try {
        const { data } = await window.axios.get("/admin/live", {
            params: { after: liveLatestId },
        });
        renderLiveList(data.events || []);
        setLiveCount(data.unread || 0);

        if (liveLatestId > 0) {
            (data.fresh || []).forEach(pushLiveToast);
            if (data.stamp && data.stamp !== liveStamp && livePages() && !liveBusy()) {
                swapAdminContent(window.location.href, { push: false, silent: true });
            }
        }

        liveStamp = data.stamp || liveStamp;
        liveLatestId = data.latest_id || liveLatestId;
    } catch {
        // تجاهل فشل الشبكة المؤقت
    }
};

document.addEventListener("click", (event) => {
    const toggle = event.target.closest("[data-live-toggle]");
    if (toggle) {
        const menu = document.querySelector("[data-live-menu]");
        if (menu) {
            menu.hidden = !menu.hidden;
        }
        return;
    }
    if (!event.target.closest("[data-live-bell]")) {
        const menu = document.querySelector("[data-live-menu]");
        if (menu) {
            menu.hidden = true;
        }
    }
});

document.querySelector("[data-live-read]")?.addEventListener("click", async () => {
    try {
        await window.axios.post("/admin/live/read");
        setLiveCount(0);
    } catch {
        //
    }
});

pollLive();
setInterval(pollLive, 5000);

const settingsPickerCount = () => {
    const root = document.querySelector("[data-product-picker]");
    if (!root) {
        return;
    }
    const count = root.querySelectorAll(
        "[data-product-picker-item] input[type=checkbox]:checked",
    ).length;
    const label = root.querySelector("[data-product-picker-count]");
    if (label) {
        label.textContent = String(count);
    }
};

const syncSettingsScope = () => {
    const selected = document.querySelector(
        '[data-sold-scope][value="selected"]',
    )?.checked;
    const picker = document.querySelector("[data-product-picker]");
    if (picker) {
        picker.dataset.scope = selected ? "selected" : "all";
    }
};

document.addEventListener("shown.bs.tab", (event) => {
    const tab = event.target.closest("[data-settings-tab]");
    if (!tab) {
        return;
    }
    const input = document.querySelector("[data-settings-active-tab]");
    if (input) {
        input.value = tab.getAttribute("data-settings-tab") || "app";
    }
});

document.addEventListener("submit", (event) => {
    const form = event.target.closest("[data-wipe-products-form]");
    if (!form) {
        return;
    }
    const phrase = (form.getAttribute("data-wipe-phrase") || "").trim();
    const typed = (form.querySelector("[data-wipe-products-input]")?.value || "").trim();
    if (typed !== phrase) {
        event.preventDefault();
        window.alert("اكتب «" + phrase + "» للتأكيد.");
        return;
    }
    if (!window.confirm(form.getAttribute("data-wipe-confirm") || "")) {
        event.preventDefault();
    }
});

document.addEventListener("input", (event) => {
    const search = event.target.closest("[data-product-picker-search]");
    if (!search) {
        return;
    }
    const query = search.value.trim().toLowerCase();
    document.querySelectorAll("[data-product-picker-item]").forEach((row) => {
        const name = (row.dataset.name || "").toLowerCase();
        row.hidden = query !== "" && !name.includes(query);
    });
});

document.addEventListener("change", (event) => {
    if (event.target.closest("[data-sold-scope]")) {
        syncSettingsScope();
    }
    if (event.target.closest("[data-product-picker-item]")) {
        settingsPickerCount();
    }
});

document.addEventListener("click", (event) => {
    if (event.target.closest("[data-product-picker-all]")) {
        document
            .querySelectorAll("[data-product-picker-item]:not([hidden]) input[type=checkbox]")
            .forEach((checkbox) => {
                checkbox.checked = true;
            });
        settingsPickerCount();
    }
    if (event.target.closest("[data-product-picker-none]")) {
        document
            .querySelectorAll("[data-product-picker-item]:not([hidden]) input[type=checkbox]")
            .forEach((checkbox) => {
                checkbox.checked = false;
            });
        settingsPickerCount();
    }
});

window.addEventListener("admin:content-ready", () => {
    syncSettingsScope();
    settingsPickerCount();
});

syncSettingsScope();
settingsPickerCount();

const bindProductAiCopy = (root = document) => {
    root.querySelectorAll("[data-product-ai-copy]").forEach((box) => {
        if (!(box instanceof HTMLElement) || box.dataset.bound === "1") {
            return;
        }
        box.dataset.bound = "1";

        const form = box.closest("form");
        const endpoint = box.dataset.endpoint;
        const status = box.querySelector("[data-ai-status]");
        const button = box.querySelector("[data-ai-generate]");
        if (!form || !endpoint) {
            return;
        }

        let timer = null;
        let lastName = "";
        let busy = false;

        const field = (name) => form.querySelector(`[name="${name}"]`);
        const aiField = (key) => box.querySelector(`[data-ai-field="${key}"]`);
        const empty = (el) => !el || String(el.value).trim() === "";
        const emptyOrZero = (el) => empty(el) || String(el.value).trim() === "0";

        const payload = () => ({
            name: field("name")?.value.trim() || "",
            category_id: field("category_id")?.value || "",
            description: field("description")?.value || "",
            weight_label: field("weight_label")?.value || "",
            quantity_label: field("quantity_label")?.value || "",
            piece_count: field("piece_count")?.value || "",
        });

        const setStatus = (text, show = true) => {
            if (!(status instanceof HTMLElement)) {
                return;
            }
            status.hidden = !show || !text;
            status.textContent = text || "";
        };

        const fillText = (el, value, overwrite) => {
            if (!el || value == null) {
                return;
            }
            const text = String(value).trim();
            if (text === "" || (!overwrite && !empty(el))) {
                return;
            }
            el.value = text;
        };

        const fillNumber = (el, value, overwrite) => {
            if (!el || value == null || value === "") {
                return;
            }
            if (!overwrite && !emptyOrZero(el)) {
                return;
            }
            el.value = String(value);
        };

        const applyCopy = (copy, force) => {
            fillText(field("description"), copy.description, force);
            fillText(aiField("description"), copy.description, force);
            if (copy.category_id && (force || empty(field("category_id")))) {
                const select = field("category_id");
                if (select) {
                    select.value = String(copy.category_id);
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                }
            }
            fillNumber(field("price"), copy.price, false);
            fillNumber(field("stock"), copy.stock, false);
            fillNumber(field("piece_count"), copy.piece_count, false);
            fillText(field("weight_label"), copy.weight_label, false);
            fillText(field("quantity_label"), copy.quantity_label, false);
            fillText(aiField("benefits"), copy.benefits, force);
            fillText(aiField("keywords"), copy.keywords, force);
            fillText(aiField("usage_instructions"), copy.usage_instructions, force);
        };

        const generate = async (force = false) => {
            const data = payload();
            if (busy) {
                return;
            }
            if (data.name.length < 3) {
                if (force) {
                    setStatus("أدخل اسم المنتج أولاً.");
                }
                return;
            }

            busy = true;
            box.classList.add("is-busy");
            if (button instanceof HTMLButtonElement) {
                button.disabled = true;
            }
            setStatus("جاري التوليد...");

            try {
                const { data: copy } = await window.axios.post(endpoint, data);
                applyCopy(copy, force);
                setStatus("", false);
            } catch (error) {
                const message =
                    error?.response?.data?.message || "تعذّر التوليد الآن.";
                setStatus(message);
            } finally {
                busy = false;
                box.classList.remove("is-busy");
                if (button instanceof HTMLButtonElement) {
                    button.disabled = false;
                }
            }
        };

        const schedule = () => {
            if (box.dataset.aiAuto === "0") {
                return;
            }
            const name = payload().name;
            if (name.length < 3 || name === lastName) {
                return;
            }
            lastName = name;
            clearTimeout(timer);
            timer = setTimeout(() => generate(false), 700);
        };

        if (box.dataset.aiAuto !== "0") {
            field("name")?.addEventListener("blur", schedule);
            field("category_id")?.addEventListener("change", () => {
                lastName = "";
                schedule();
            });
        }
        button?.addEventListener("click", () => generate(true));
    });
};

bindProductAiCopy();
window.addEventListener("admin:content-ready", () => bindProductAiCopy());

const bindProductCopyBulk = (root = document) => {
    const panel = root.querySelector("[data-product-copy-bulk]");
    if (!(panel instanceof HTMLElement) || panel.dataset.bound === "1") {
        return;
    }
    panel.dataset.bound = "1";

    const statusUrl = panel.dataset.statusUrl;
    const cancelUrl = panel.dataset.cancelUrl;
    const title = panel.querySelector("[data-bulk-title]");
    const count = panel.querySelector("[data-bulk-count]");
    const bar = panel.querySelector("[data-bulk-bar]");
    const detail = panel.querySelector("[data-bulk-detail]");
    const cancelBtn = panel.querySelector("[data-bulk-cancel]");
    const submit = root.querySelector("[data-product-copy-bulk-submit]");
    let timer = null;

    const setSubmitBusy = (busy) => {
        if (!(submit instanceof HTMLButtonElement)) {
            return;
        }
        if (!submit.dataset.originalLabel) {
            submit.dataset.originalLabel = submit.innerHTML;
        }
        submit.disabled = busy;
        submit.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm ms-1"></span> جاري البدء...'
            : submit.dataset.originalLabel;
    };

    const render = (data) => {
        const total = Number(data.total || 0);
        const processed = Number(data.processed || 0);
        const ok = Number(data.ok || 0);
        const failed = Number(data.failed || 0);
        const percent = Number(data.percent || 0);
        const running = Boolean(data.running);
        const finished = Boolean(data.finished_at);
        const cancelled = Boolean(data.cancelled);

        if (cancelled || (total === 0 && !running)) {
            panel.hidden = true;
            setSubmitBusy(false);
            if (cancelBtn instanceof HTMLButtonElement) {
                cancelBtn.hidden = true;
            }
            if (cancelled) {
                return;
            }
        }

        if (total > 0 && (running || finished)) {
            panel.hidden = false;
        } else if (!running) {
            panel.hidden = true;
        }

        if (count) {
            count.textContent = total > 0 ? `${processed} / ${total}` : "";
        }
        if (bar instanceof HTMLElement) {
            bar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
            bar.setAttribute("aria-valuenow", String(percent));
        }

        if (cancelBtn instanceof HTMLButtonElement) {
            cancelBtn.hidden = !running;
            cancelBtn.disabled = false;
        }

        if (running) {
            if (title) {
                title.textContent = "جاري توليد المحتوى في الخلفية...";
            }
            if (detail) {
                detail.textContent =
                    "يمكنك متابعة استخدام لوحة التحكم. سيتم تحديث التقدم تلقائياً.";
            }
            setSubmitBusy(true);
            return;
        }

        setSubmitBusy(false);

        if (total === 0) {
            panel.hidden = true;
            return;
        }

        if (title) {
            title.textContent = failed > 0 ? "اكتمل التوليد مع بعض الأخطاء" : "اكتمل توليد المحتوى";
        }
        if (detail) {
            detail.textContent =
                failed > 0
                    ? `تم توليد المحتوى لـ ${ok} منتج، وفشل ${failed}.`
                    : `تم توليد المحتوى لجميع المنتجات (${ok}).`;
        }
    };

    const poll = async () => {
        if (!statusUrl) {
            return;
        }

        try {
            const { data } = await window.axios.get(statusUrl);
            render(data);
            if (data.running) {
                timer = window.setTimeout(poll, 4000);
            }
        } catch {
            timer = window.setTimeout(poll, 6000);
        }
    };

    const form = root.querySelector("[data-product-copy-bulk-form]");
    form?.addEventListener("submit", () => {
        setSubmitBusy(true);
        window.setTimeout(poll, 1500);
    });

    cancelBtn?.addEventListener("click", async () => {
        if (!cancelUrl) {
            return;
        }
        if (!window.confirm("هل تريد إلغاء توليد المحتوى المتبقي في الخلفية؟")) {
            return;
        }

        if (cancelBtn instanceof HTMLButtonElement) {
            cancelBtn.disabled = true;
        }

        try {
            const { data } = await window.axios.post(cancelUrl);
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
            render(data);
        } catch {
            if (cancelBtn instanceof HTMLButtonElement) {
                cancelBtn.disabled = false;
            }
        }
    });

    poll();
};

bindProductCopyBulk();
window.addEventListener("admin:content-ready", () => bindProductCopyBulk());

const selectedLookupIds = (root) =>
    [...root.querySelectorAll("[data-product-lookup-selected] [data-id]")]
        .map((chip) => Number(chip.dataset.id))
        .filter((id) => id > 0);

const lookupEmptyState = (root) => {
    const selected = root.querySelector("[data-product-lookup-selected]");
    if (!selected) {
        return;
    }
    selected.querySelector(".product-lookup-empty-state")?.remove();
    const hasChip = selected.querySelector("[data-id]");
    const allowEmpty = root.dataset.allowEmpty === "1";
    const multiple = root.dataset.multiple === "1";
    if (hasChip) {
        selected.querySelectorAll(`input[name="${root.dataset.name}"][value=""]`).forEach((input) => input.remove());
        return;
    }
    if (allowEmpty && !multiple) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = root.dataset.name;
        input.value = "";
        selected.append(input);
        const hint = document.createElement("div");
        hint.className = "product-lookup-empty-state";
        hint.textContent = root.dataset.emptyLabel || "بدون اختيار";
        selected.append(hint);
    }
};

const lookupChip = (root, item) => {
    const chip = document.createElement("div");
    chip.className = "product-lookup-chip";
    chip.dataset.id = String(item.id);

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = root.dataset.name;
    input.value = String(item.id);

    const copy = document.createElement("span");
    const title = document.createElement("strong");
    title.textContent = item.name || "";
    const meta = document.createElement("small");
    meta.textContent = [item.sku, item.price_label].filter(Boolean).join(" · ");
    copy.append(title, meta);

    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "product-lookup-remove";
    remove.setAttribute("data-product-lookup-remove", "");
    remove.setAttribute("aria-label", "إزالة");
    remove.innerHTML = '<i class="bi bi-x-lg"></i>';

    chip.append(input, copy, remove);
    return chip;
};

const addLookupItem = (root, item) => {
    if (!item?.id) {
        return;
    }
    const selected = root.querySelector("[data-product-lookup-selected]");
    if (!selected) {
        return;
    }
    const multiple = root.dataset.multiple === "1";
    if (!multiple) {
        selected.replaceChildren();
    } else if (selectedLookupIds(root).includes(Number(item.id))) {
        return;
    }
    selected.append(lookupChip(root, item));
    lookupEmptyState(root);
};

const renderLookupResults = (root, items) => {
    const box = root.querySelector("[data-product-lookup-results]");
    if (!box) {
        return;
    }
    box.replaceChildren();
    if (!items.length) {
        const empty = document.createElement("div");
        empty.className = "product-lookup-empty";
        empty.textContent = "لا توجد نتائج";
        box.append(empty);
        box.hidden = false;
        return;
    }
    items.forEach((item) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "product-lookup-result";
        button.dataset.id = String(item.id);
        const title = document.createElement("strong");
        title.textContent = item.name || "";
        const meta = document.createElement("small");
        meta.textContent = [item.sku, item.barcode, item.price_label].filter(Boolean).join(" · ");
        button.append(title, meta);
        button.addEventListener("click", () => {
            addLookupItem(root, item);
            const search = root.querySelector("[data-product-lookup-q]");
            if (search) {
                search.value = "";
            }
            box.hidden = true;
            box.replaceChildren();
        });
        box.append(button);
    });
    box.hidden = false;
};

const searchLookup = async (root) => {
    const search = root.querySelector("[data-product-lookup-q]");
    const term = (search?.value || "").trim();
    const box = root.querySelector("[data-product-lookup-results]");
    if (term.length < 1) {
        if (box) {
            box.hidden = true;
            box.replaceChildren();
        }
        return;
    }

    const params = new URLSearchParams({
        q: term,
        exclude: selectedLookupIds(root).join(","),
    });
    if (root.dataset.except) {
        params.set("except", root.dataset.except);
    }
    if (root.dataset.giftOnly === "1") {
        params.set("gift_only", "1");
    }
    if (root.dataset.excludeGifts === "1") {
        params.set("exclude_gifts", "1");
    }

    try {
        const { data } = await window.axios.get(root.dataset.endpoint, { params });
        renderLookupResults(root, data.items || []);
    } catch {
        renderLookupResults(root, []);
    }
};

const bindProductLookups = () => {
    document.querySelectorAll("[data-product-lookup]").forEach((root) => {
        if (!(root instanceof HTMLElement) || root.dataset.bound === "1") {
            return;
        }
        root.dataset.bound = "1";
        lookupEmptyState(root);

        let timer = null;
        root.querySelector("[data-product-lookup-q]")?.addEventListener("input", () => {
            clearTimeout(timer);
            timer = setTimeout(() => searchLookup(root), 220);
        });

        root.addEventListener("click", (event) => {
            const remove = event.target.closest("[data-product-lookup-remove]");
            if (!remove) {
                return;
            }
            event.preventDefault();
            remove.closest("[data-id]")?.remove();
            lookupEmptyState(root);
        });
    });
};

document.addEventListener("click", (event) => {
    document.querySelectorAll("[data-product-lookup]").forEach((root) => {
        if (!root.contains(event.target)) {
            const box = root.querySelector("[data-product-lookup-results]");
            if (box) {
                box.hidden = true;
            }
        }
    });
});

bindProductLookups();
window.addEventListener("admin:content-ready", bindProductLookups);

window.adminAddProductLookup = (root, item) => {
    const target = typeof root === "string" ? document.querySelector(root) : root;
    if (target instanceof HTMLElement) {
        addLookupItem(target, item);
    }
};

const clearGiftModalErrors = (form) => {
    if (!form) {
        return;
    }
    form.querySelectorAll("[data-gift-error]").forEach((node) => {
        node.textContent = "";
    });
    form.querySelectorAll("[data-gift-field]").forEach((field) => {
        field.classList.remove("is-invalid");
    });
    const alert = form.querySelector("[data-gift-form-error]");
    if (alert) {
        alert.hidden = true;
        alert.textContent = "";
    }
};

const resetGiftModal = (modal) => {
    const form = modal?.querySelector("#giftProductForm");
    if (!form) {
        return;
    }
    form.reset();
    clearGiftModalErrors(form);
    const stock = form.querySelector('[name="stock"]');
    if (stock) {
        stock.value = "10";
    }
    const price = form.querySelector('[name="price"]');
    if (price) {
        price.value = "0";
    }
    const mainPicker = modal.querySelector("[data-gift-main-picker] [data-product-lookup]");
    if (mainPicker instanceof HTMLElement) {
        const selected = mainPicker.querySelector("[data-product-lookup-selected]");
        selected?.replaceChildren();
        lookupEmptyState(mainPicker);
    }
};

const showGiftModalErrors = (form, payload) => {
    clearGiftModalErrors(form);
    const errors = payload?.errors || {};
    Object.entries(errors).forEach(([key, messages]) => {
        const message = Array.isArray(messages) ? messages[0] : String(messages || "");
        const field = form.querySelector(`[data-gift-field="${key}"]`);
        if (field) {
            field.classList.add("is-invalid");
        }
        const errorNode = form.querySelector(`[data-gift-error="${key}"]`);
        if (errorNode) {
            errorNode.textContent = message;
        }
    });
    const alert = form.querySelector("[data-gift-form-error]");
    if (alert && payload?.message) {
        alert.hidden = false;
        alert.textContent = payload.message;
    }
};

const applySelectedExistingGift = (modal) => {
    const selectedRoot = modal?.querySelector("[data-gift-existing-picker] [data-product-lookup]");
    const targetRoot = document.querySelector("[data-gift-product-picker] [data-product-lookup]");
    const selectedChip = selectedRoot?.querySelector("[data-product-lookup-selected] [data-id]");
    const selectedNode = selectedChip instanceof HTMLElement ? selectedChip.cloneNode(true) : null;
    const alert = modal?.querySelector("[data-gift-form-error]");

    if (!(selectedNode instanceof HTMLElement) || !(targetRoot instanceof HTMLElement)) {
        if (alert) {
            alert.hidden = false;
            alert.textContent = "اختر منتجاً موجوداً أولاً ليتم استخدامه كهدية.";
        }
        return;
    }

    const selectedBox = targetRoot.querySelector("[data-product-lookup-selected]");
    if (selectedBox) {
        selectedBox.replaceChildren();
        selectedBox.append(selectedNode);
        lookupEmptyState(targetRoot);
    }

    if (alert) {
        alert.hidden = true;
        alert.textContent = "";
    }

    window.bootstrap.Modal.getOrCreateInstance(modal).hide();
    resetGiftModal(modal);
};

const bindGiftProductModal = () => {
    const modal = document.getElementById("giftProductModal");
    const form = modal?.querySelector("#giftProductForm");
    if (!(modal instanceof HTMLElement) || !(form instanceof HTMLFormElement) || form.dataset.bound === "1") {
        return;
    }
    form.dataset.bound = "1";

    modal.addEventListener("show.bs.modal", () => {
        resetGiftModal(modal);
        bindProductLookups();
    });

    form.querySelector("[data-gift-use-existing]")?.addEventListener("click", () => {
        applySelectedExistingGift(modal);
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearGiftModalErrors(form);

        const submit = form.querySelector("[data-gift-submit]");
        if (submit instanceof HTMLButtonElement) {
            submit.disabled = true;
        }

        const formData = new FormData(form);
        const currentId = Number(modal.dataset.currentProductId || 0);
        if (currentId > 0) {
            formData.set("current_product_id", String(currentId));
        }

        try {
            const { data } = await window.axios.post(modal.dataset.giftQuickEndpoint || "", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            const giftPicker = document.querySelector("[data-gift-product-picker] [data-product-lookup]");
            if (giftPicker instanceof HTMLElement) {
                addLookupItem(giftPicker, data);
            }

            window.bootstrap.Modal.getOrCreateInstance(modal).hide();
            resetGiftModal(modal);
        } catch (error) {
            const status = error?.response?.status;
            const payload = error?.response?.data || {};
            if (status === 422) {
                showGiftModalErrors(form, payload);
            } else {
                showGiftModalErrors(form, {
                    message: payload.message || "تعذّر حفظ منتج الهدية. حاول مرة أخرى.",
                });
            }
        } finally {
            if (submit instanceof HTMLButtonElement) {
                submit.disabled = false;
            }
        }
    });
};

bindGiftProductModal();
window.addEventListener("admin:content-ready", bindGiftProductModal);

const bindCouponForm = () => {
    const type = document.getElementById("coupon_type");
    const applies = document.getElementById("applies_to");
    if (!type || !applies) {
        return;
    }
    const sync = () => {
        const valueWrap = document.getElementById("value_wrap");
        const products = document.getElementById("products_wrap");
        const categories = document.getElementById("categories_wrap");
        if (valueWrap) {
            valueWrap.style.display = type.value === "free_shipping" ? "none" : "";
        }
        if (products) {
            products.style.display = applies.value === "products" ? "" : "none";
        }
        if (categories) {
            categories.style.display = applies.value === "categories" ? "" : "none";
        }
    };
    if (type.dataset.bound !== "1") {
        type.dataset.bound = "1";
        type.addEventListener("change", sync);
        applies.addEventListener("change", sync);
    }
    sync();
};

bindCouponForm();
window.addEventListener("admin:content-ready", bindCouponForm);

const resetDiscoveryForm = (form) => {
    if (!form) {
        return;
    }
    clearFormErrors(form);
    setDeliveryFormMode(form, null);
    setFormField(form, "phrase", "");
    setFormField(form, "sort_order", "0");
    setFormField(form, "is_active", "1");
};

const fillDiscoveryForm = (form, data) => {
    if (!form) {
        return;
    }
    clearFormErrors(form);
    setDeliveryFormMode(form, data.id);
    setFormField(form, "phrase", data.phrase || "");
    setFormField(form, "sort_order", data.sortOrder ?? "0");
    setFormField(form, "is_active", data.isActive);
};

const resetSearchPhraseForm = (form) => resetDiscoveryForm(form);

const fillSearchPhraseForm = (form, data) => fillDiscoveryForm(form, data);

const bindSearchPlaceholdersPage = () => {
    const phraseModal = document.getElementById("searchPhraseModal");
    if (phraseModal?.dataset.open === "1") {
        window.bootstrap.Modal.getOrCreateInstance(phraseModal).show();
    }
    const smartModal = document.getElementById("searchSmartModal");
    if (smartModal?.dataset.open === "1") {
        window.bootstrap.Modal.getOrCreateInstance(smartModal).show();
    }
    const trendingModal = document.getElementById("searchTrendingModal");
    if (trendingModal?.dataset.open === "1") {
        window.bootstrap.Modal.getOrCreateInstance(trendingModal).show();
    }
};

const renderCustomerSearchLogs = (name, logs) => {
    const title = document.querySelector("[data-customer-logs-title]");
    const body = document.querySelector("[data-customer-logs-body]");
    if (title) {
        title.textContent = name ? `كلمات بحث: ${name}` : "كلمات البحث";
    }
    if (!(body instanceof HTMLElement)) {
        return;
    }
    const rows = Array.isArray(logs) ? logs : [];
    if (rows.length === 0) {
        body.innerHTML = `<tr><td colspan="3" class="text-muted">لا توجد كلمات.</td></tr>`;
        return;
    }
    body.innerHTML = rows
        .map((row) => {
            const query = escLive(row.query || "");
            const date = escLive(row.date || "—");
            const product = row.found
                ? escLive(row.product || "منتج موجود")
                : '<span class="text-warning">غير موجود</span>';
            return `<tr><td class="fw-semibold">${query}</td><td>${date}</td><td>${product}</td></tr>`;
        })
        .join("");
};

document.addEventListener("show.bs.modal", (event) => {
    const modal = event.target;
    const trigger = event.relatedTarget;
    if (!(modal instanceof HTMLElement) || !(trigger instanceof HTMLElement)) {
        return;
    }
    if (modal.id === "searchPhraseModal") {
        const form = modal.querySelector("#searchPhraseForm");
        if (trigger.hasAttribute("data-search-phrase-edit")) {
            fillSearchPhraseForm(form, trigger.dataset);
        } else if (trigger.hasAttribute("data-search-phrase-create")) {
            resetSearchPhraseForm(form);
        }
        return;
    }
    if (modal.id === "searchSmartModal") {
        const form = modal.querySelector("#searchSmartForm");
        if (trigger.hasAttribute("data-search-smart-edit")) {
            fillDiscoveryForm(form, trigger.dataset);
        } else if (trigger.hasAttribute("data-search-smart-create")) {
            resetDiscoveryForm(form);
        }
        return;
    }
    if (modal.id === "searchTrendingModal") {
        const form = modal.querySelector("#searchTrendingForm");
        if (trigger.hasAttribute("data-search-trending-edit")) {
            fillDiscoveryForm(form, trigger.dataset);
        } else if (trigger.hasAttribute("data-search-trending-create")) {
            resetDiscoveryForm(form);
        }
        return;
    }
    if (modal.id === "customerSearchLogsModal" && trigger.hasAttribute("data-customer-search-logs")) {
        let logs = [];
        try {
            logs = JSON.parse(trigger.getAttribute("data-logs") || "[]");
        } catch (_) {
            logs = [];
        }
        renderCustomerSearchLogs(trigger.dataset.name || "", logs);
    }
});

bindSearchPlaceholdersPage();
window.addEventListener("admin:content-ready", bindSearchPlaceholdersPage);

