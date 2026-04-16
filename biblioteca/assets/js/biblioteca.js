/* ============================================================
   BIBLIOTECA GOBETTI – JavaScript client-side
   ============================================================ */

'use strict';

// ── Utility ────────────────────────────────────────────────

/**
 * Mostra un messaggio toast Bootstrap.
 * @param {string} message  Testo del messaggio
 * @param {string} type     Tipo Bootstrap: success | danger | warning | info
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const id   = 'toast-' + Date.now();
    const html = `
      <div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert"
           aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto"
                  data-bs-dismiss="toast"></button>
        </div>
      </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const el    = document.getElementById(id);
    const toast = new bootstrap.Toast(el, { delay: 4000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/**
 * Invia una richiesta fetch JSON verso api.php e restituisce la risposta.
 * @param {string} endpoint  Nome dell'endpoint (action)
 * @param {object} data      Dati del body
 * @param {string} method    Metodo HTTP
 */
async function apiCall(endpoint, data = {}, method = 'POST') {
    // Ottieni il token CSRF dalla meta tag
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf     = csrfMeta ? csrfMeta.content : '';

    const body = new URLSearchParams({ action: endpoint, csrf_token: csrf, ...data });

    const response = await fetch('api.php', {
        method,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    });

    if (!response.ok) throw new Error('Errore di rete: ' + response.status);
    return response.json();
}

// ── Catalogo libri ──────────────────────────────────────────

/**
 * Gestione del form ricerca libri con aggiornamento dinamico via AJAX.
 */
function initCatalogSearch() {
    const form    = document.getElementById('searchForm');
    const results = document.getElementById('catalogResults');
    const spinner = document.getElementById('searchSpinner');
    if (!form || !results) return;

    let debounceTimer;

    const doSearch = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            const params = new URLSearchParams(new FormData(form));
            params.set('action', 'search_books');

            if (spinner) spinner.classList.remove('d-none');
            try {
                const res = await fetch('api.php?' + params.toString());
                const data = await res.json();
                if (data.success) {
                    renderBooks(data.libri, results);
                }
            } catch (e) {
                console.error('Errore ricerca:', e);
            } finally {
                if (spinner) spinner.classList.add('d-none');
            }
        }, 400);
    };

    // Ricerca automatica al cambio input
    form.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('input', doSearch);
        el.addEventListener('change', doSearch);
    });

    form.addEventListener('submit', e => {
        e.preventDefault();
        doSearch();
    });
}

/**
 * Renderizza le schede libro nel contenitore.
 */
function renderBooks(libri, container) {
    if (!libri || libri.length === 0) {
        container.innerHTML = `
          <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-search fs-1"></i>
            <p class="mt-2">Nessun libro trovato.</p>
          </div>`;
        return;
    }

    container.innerHTML = libri.map(libro => {
        const disponibili = parseInt(libro.copie_disponibili) || 0;
        const badgeClass  = disponibili > 0 ? 'success' : 'danger';
        const badgeText   = disponibili > 0
            ? `${disponibili} disponibile${disponibili > 1 ? 'i' : ''}`
            : 'Non disponibile';

        const cover = libro.copertina
            ? `<img src="uploads/copertine/${escHtml(libro.copertina)}" class="book-cover" alt="Copertina">`
            : `<div class="book-cover-placeholder">
                 <i class="bi bi-book fs-1"></i>
                 <small class="mt-1">${escHtml(libro.tipologia)}</small>
               </div>`;

        return `
          <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3">
            <div class="book-card d-flex flex-column">
              <a href="libro.php?id=${libro.id}" class="text-decoration-none text-dark">
                ${cover}
                <div class="p-2 flex-grow-1 d-flex flex-column">
                  <div class="book-title mb-1">${escHtml(libro.titolo)}</div>
                  <div class="book-author mb-1">${escHtml(libro.autore)}</div>
                  <div class="book-meta mb-auto">${libro.anno || ''}</div>
                  <span class="badge bg-${badgeClass} availability-badge mt-2">${badgeText}</span>
                </div>
              </a>
            </div>
          </div>`;
    }).join('');
}

/**
 * Escaping HTML per sicurezza output lato client.
 */
function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

// ── Prenotazione (prenota.php) ──────────────────────────────

/**
 * Carica dinamicamente la lista studenti al cambio classe.
 */
function initClasseSelect() {
    const selectClasse   = document.getElementById('id_classe');
    const studentiSection = document.getElementById('studenti-section');
    if (!selectClasse || !studentiSection) return;

    selectClasse.addEventListener('change', async () => {
        const idClasse = selectClasse.value;
        if (!idClasse) {
            studentiSection.innerHTML = '';
            return;
        }
        try {
            const res  = await fetch(`api.php?action=get_studenti&id_classe=${idClasse}`);
            const data = await res.json();
            if (data.success && data.studenti) {
                renderStudenti(data.studenti, studentiSection);
            }
        } catch (e) {
            studentiSection.innerHTML = '<p class="text-danger">Errore nel caricamento studenti.</p>';
        }
    });
}

function renderStudenti(studenti, container) {
    if (!studenti.length) {
        container.innerHTML = '<p class="text-muted">Nessuno studente in questa classe.</p>';
        return;
    }
    container.innerHTML = `
      <label class="fw-semibold mb-2 d-block">Seleziona studenti:</label>
      <div class="row g-2">
        ${studenti.map(s => `
          <div class="col-sm-6 col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="studenti[]"
                     value="${s.IDUtente}" id="stu-${s.IDUtente}">
              <label class="form-check-label" for="stu-${s.IDUtente}">
                ${escHtml(s.cognomeStu)} ${escHtml(s.nomeStu)}
              </label>
            </div>
          </div>`).join('')}
      </div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllStu">
          Seleziona tutti
        </button>
      </div>`;

    document.getElementById('selectAllStu')?.addEventListener('click', () => {
        container.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = true);
    });
}

// ── Avvisami ────────────────────────────────────────────────

function initAvvisami() {
    document.querySelectorAll('.btn-avvisami').forEach(btn => {
        btn.addEventListener('click', async () => {
            const idLibro = btn.dataset.idLibro;
            try {
                const data = await apiCall('avvisami', { id_libro: idLibro });
                if (data.success) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-bell-fill me-1"></i>Richiesta inviata';
                    btn.classList.replace('btn-outline-secondary', 'btn-secondary');
                    showToast('Sarai avvisato quando il libro sarà disponibile.', 'success');
                } else {
                    showToast(data.message || 'Errore.', 'danger');
                }
            } catch (e) {
                showToast('Errore di rete.', 'danger');
            }
        });
    });
}

// ── Conferma operazioni sensibili ──────────────────────────

function initConfirmButtons() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            const msg = el.dataset.confirm || 'Sei sicuro di voler procedere?';
            if (!confirm(msg)) e.preventDefault();
        });
    });
}

// ── Gestione libri (AJAX inline edit) ──────────────────────

function initGestioneLibri() {
    // Anteprima immagine copertina in upload
    const inputCopertina = document.getElementById('copertina');
    const preview        = document.getElementById('coverPreview');
    if (inputCopertina && preview) {
        inputCopertina.addEventListener('change', () => {
            const file = inputCopertina.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

// ── Auto-blacklist banner ────────────────────────────────────

async function checkBlacklist() {
    const banner = document.getElementById('blacklistBanner');
    if (!banner) return;
    try {
        const res  = await fetch('api.php?action=check_blacklist');
        const data = await res.json();
        if (data.blacklisted) {
            banner.classList.remove('d-none');
        }
    } catch (_) { /* silenzioso */ }
}

// ── Inizializzazione DOMContentLoaded ───────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initCatalogSearch();
    initClasseSelect();
    initAvvisami();
    initConfirmButtons();
    initGestioneLibri();
    checkBlacklist();

    // Inizializza i tooltip Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // Auto-dismiss degli alert dopo 5 secondi
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
});
