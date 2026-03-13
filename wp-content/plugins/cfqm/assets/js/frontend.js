/**
 * ChangeFluent Quote Manager — Frontend JavaScript
 *
 * Handles:
 *   - Quote Portal: area selection, live totals, e-sign, accept/decline, amendment request
 *   - Homeowner Dashboard: tab filter, search, compare mode
 *   - Trades Dashboard: amendment actions, payment link creation
 *   - Query Form: AJAX submission
 *   - Magic Link Form: AJAX submission
 */

(function ($) {
  'use strict';

  const AJAX_URL = (typeof cfqmData !== 'undefined') ? cfqmData.ajaxUrl : '/wp-admin/admin-ajax.php';
  const NONCE    = (typeof cfqmData !== 'undefined') ? cfqmData.nonce   : '';

  // ── Utility ────────────────────────────────────────────────────────────

  function showSpinner(btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
      btn.disabled = true;
      const sp = btn.querySelector('.cfqm-spinner');
      if (sp) sp.style.display = 'inline-block';
    }
  }
  function hideSpinner(btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
      btn.disabled = false;
      const sp = btn.querySelector('.cfqm-spinner');
      if (sp) sp.style.display = 'none';
    }
  }
  function showAlert(id, message, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = 'cfqm-alert cfqm-alert--' + (type || 'info');
    el.textContent = message;
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  function hideEl(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  }
  function showEl(id, display) {
    const el = document.getElementById(id);
    if (el) el.style.display = display || 'block';
  }

  // ── Quote Portal ───────────────────────────────────────────────────────

  const portal = document.getElementById('cfqm-portal');
  if (portal) {

    // Parse quote data embedded in page
    const quoteDataEl = document.getElementById('cfqm-quote-data');
    const qd = quoteDataEl ? JSON.parse(quoteDataEl.textContent) : {};

    // Area selection (optional areas only)
    function recalcTotals() {
      let selectedTotal = 0;
      document.querySelectorAll('.cfqm-area-card').forEach(function (card) {
        const areaType = card.dataset.areaType;
        const price    = parseFloat(card.dataset.price || 0);

        let selected = true;
        if (areaType === 'optional') {
          const cb = card.querySelector('.cfqm-area-toggle');
          selected = cb ? cb.checked : false;
          card.classList.toggle('cfqm-selected', selected);
        }
        if (selected) selectedTotal += price;
      });

      // VAT
      let vatAmt = 0;
      if (qd.vatEnabled) {
        vatAmt = selectedTotal * (qd.vatRate / 100);
      }
      const grand = selectedTotal + vatAmt;

      // Deposit
      const depositAmt = qd.depositPct > 0 ? (grand * qd.depositPct / 100) : 0;

      // Update display
      const fmt = function (v) { return '£' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
      const setEl = function (id, v) {
        const el = document.getElementById(id);
        if (el) el.textContent = v;
      };
      setEl('cfqm-display-subtotal',  fmt(selectedTotal));
      setEl('cfqm-display-vat',       fmt(vatAmt));
      setEl('cfqm-display-total',     fmt(grand));
      setEl('cfqm-display-deposit',   fmt(depositAmt));

      return { selectedTotal, vatAmt, grand, depositAmt };
    }

    // Bind toggles on optional area cards
    document.querySelectorAll('.cfqm-area-toggle').forEach(function (cb) {
      cb.addEventListener('change', recalcTotals);
    });
    recalcTotals(); // initial

    // Accept
    const btnAccept = document.getElementById('cfqm-btn-accept');
    if (btnAccept) {
      btnAccept.addEventListener('click', function () {
        const sig      = document.getElementById('cfqm-signature');
        const sigDate  = document.getElementById('cfqm-sig-date');
        const sigCheck = document.getElementById('cfqm-sig-confirm');

        if (!sig || !sig.value.trim()) {
          showAlert('cfqm-portal-msg', 'Please type your full name to e-sign.', 'warning');
          sig && sig.focus();
          return;
        }
        if (!sigCheck || !sigCheck.checked) {
          showAlert('cfqm-portal-msg', 'Please confirm you have read the scope of work.', 'warning');
          return;
        }

        // Collect selected areas
        const selectedAreas = [];
        document.querySelectorAll('.cfqm-area-card').forEach(function (card) {
          const aType = card.dataset.areaType;
          const aId   = card.dataset.areaId;
          if (aType === 'included') {
            selectedAreas.push(aId);
          } else {
            const cb = card.querySelector('.cfqm-area-toggle');
            if (cb && cb.checked) selectedAreas.push(aId);
          }
        });

        showSpinner('cfqm-btn-accept');

        $.post(AJAX_URL, {
          action:         'cfqm_portal_accept',
          nonce:          document.getElementById('cfqm-portal-nonce') ? document.getElementById('cfqm-portal-nonce').value : NONCE,
          cfqm_token:     getPortalToken(),
          signature:      sig.value.trim(),
          signature_date: sigDate ? sigDate.value : '',
          selected_areas: selectedAreas,
        }, function (res) {
          hideSpinner('cfqm-btn-accept');
          if (res.success) {
            showAlert('cfqm-portal-msg', res.data.message || 'Quote accepted!', 'success');
            document.getElementById('cfqm-btn-accept') && (document.getElementById('cfqm-btn-accept').disabled = true);
            document.getElementById('cfqm-btn-decline') && (document.getElementById('cfqm-btn-decline').disabled = true);
            if (res.data.stripe_link) {
              const depositBtn = document.getElementById('cfqm-btn-pay-deposit');
              if (depositBtn) {
                depositBtn.href = res.data.stripe_link;
                showEl('cfqm-deposit-section');
              }
            }
          } else {
            showAlert('cfqm-portal-msg', (res.data && res.data.message) || 'Error accepting quote.', 'error');
          }
        });
      });
    }

    // Decline
    const btnDecline = document.getElementById('cfqm-btn-decline');
    if (btnDecline) {
      btnDecline.addEventListener('click', function () {
        if (!confirm('Are you sure you want to decline this quote?')) return;
        showSpinner('cfqm-btn-decline');
        $.post(AJAX_URL, {
          action:     'cfqm_portal_decline',
          nonce:      document.getElementById('cfqm-portal-nonce') ? document.getElementById('cfqm-portal-nonce').value : NONCE,
          cfqm_token: getPortalToken(),
        }, function (res) {
          hideSpinner('cfqm-btn-decline');
          if (res.success) {
            showAlert('cfqm-portal-msg', 'Quote declined.', 'info');
            document.getElementById('cfqm-btn-accept') && (document.getElementById('cfqm-btn-accept').disabled = true);
            btnDecline.disabled = true;
          } else {
            showAlert('cfqm-portal-msg', (res.data && res.data.message) || 'Error.', 'error');
          }
        });
      });
    }

    // Amendment submit
    const btnAmend = document.getElementById('cfqm-btn-amend');
    if (btnAmend) {
      btnAmend.addEventListener('click', function () {
        const message = document.getElementById('cfqm-amendment-message');
        const areaChecks = document.querySelectorAll('.cfqm-amendment-area-check:checked');
        const areas = Array.from(areaChecks).map(function (c) { return c.value; });

        if (!message || !message.value.trim()) {
          showAlert('cfqm-portal-msg', 'Please describe your amendment request.', 'warning');
          return;
        }

        showSpinner('cfqm-btn-amend');
        $.post(AJAX_URL, {
          action:            'cfqm_submit_amendment',
          nonce:             document.getElementById('cfqm-portal-nonce') ? document.getElementById('cfqm-portal-nonce').value : NONCE,
          cfqm_token:        getPortalToken(),
          areas_requested:   areas,
          amendment_message: message.value.trim(),
        }, function (res) {
          hideSpinner('cfqm-btn-amend');
          if (res.success) {
            showAlert('cfqm-portal-msg', res.data.message || 'Amendment request submitted.', 'success');
            document.getElementById('cfqm-amendment-block').style.display = 'none';
          } else {
            showAlert('cfqm-portal-msg', (res.data && res.data.message) || 'Error.', 'error');
          }
        });
      });
    }

    function getPortalToken() {
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get('cfqm_token') || '';
    }
  }

  // ── Homeowner Dashboard ────────────────────────────────────────────────

  const hwDash = document.getElementById('cfqm-hw-dashboard');
  if (hwDash) {

    // Live search
    const searchInput = document.getElementById('cfqm-hw-search');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.cfqm-quote-card').forEach(function (card) {
          const text = (card.dataset.searchtext || '').toLowerCase();
          card.classList.toggle('cfqm-quote-card--hidden', q.length > 0 && !text.includes(q));
        });
      });
    }

    // Compare checkbox management
    const compareCheckboxes = document.querySelectorAll('.cfqm-compare-checkbox');
    compareCheckboxes.forEach(function (cb) {
      cb.addEventListener('change', function () {
        const checked = document.querySelectorAll('.cfqm-compare-checkbox:checked');
        if (checked.length > 3) {
          this.checked = false;
          alert('You can compare up to 3 quotes at a time.');
          return;
        }
        if (checked.length >= 2) {
          const ids = Array.from(checked).map(function (c) { return c.value; });
          const url = new URL(window.location.href);
          ids.forEach(function (id) { url.searchParams.append('compare[]', id); });
          // Update compare bar without reload
          const bar = document.getElementById('cfqm-compare-bar');
          if (bar) showEl('cfqm-compare-bar');
        }
      });
    });

    // Run compare button
    const runCompare = document.getElementById('cfqm-run-compare');
    if (runCompare) {
      runCompare.addEventListener('click', function () {
        const panel = document.getElementById('cfqm-compare-panel');
        if (panel) {
          panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
          // Highlight cheapest
          let min = Infinity;
          let best = null;
          document.querySelectorAll('.cfqm-compare-col').forEach(function (col) {
            const totalEl = col.querySelector('p strong');
            if (totalEl && totalEl.nextSibling) {
              const val = parseFloat(totalEl.nextSibling.textContent.replace(/[£,]/g, ''));
              if (val < min) { min = val; best = col; }
            }
          });
          if (best) best.classList.add('cfqm-compare-col--best');
        }
      });
    }
  }

  // ── Trades Dashboard ──────────────────────────────────────────────────

  const tradesDash = document.getElementById('cfqm-trades-dashboard');
  if (tradesDash) {

    // Amendment actions (reissue / approve_as_is / reject)
    tradesDash.addEventListener('click', function (e) {
      const btn = e.target.closest('.cfqm-amendment-action');
      if (!btn) return;

      const quoteId = btn.dataset.quoteId;
      const action  = btn.dataset.action;
      let message   = '';

      if (action === 'reject') {
        const rejectForm = document.getElementById('cfqm-reject-' + quoteId);
        const textarea   = rejectForm ? rejectForm.querySelector('.cfqm-reject-message') : null;
        message = textarea ? textarea.value.trim() : '';
        if (!message) {
          alert('Please enter a message explaining the rejection.');
          return;
        }
      }

      btn.disabled = true;

      $.post(AJAX_URL, {
        action:           'cfqm_respond_amendment',
        nonce:            NONCE,
        quote_id:         quoteId,
        amendment_action: action,
        trade_message:    message,
      }, function (res) {
        btn.disabled = false;
        if (res.success) {
          alert(res.data.message || 'Done.');
          window.location.reload();
        } else {
          alert((res.data && res.data.message) || 'Error processing request.');
        }
      });
    });

    // Show reject form toggle
    tradesDash.addEventListener('click', function (e) {
      const btn = e.target.closest('.cfqm-amendment-reject');
      if (!btn) return;
      const id = btn.dataset.quoteId;
      const form = document.getElementById('cfqm-reject-' + id);
      if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });

    // Create payment link
    tradesDash.addEventListener('click', function (e) {
      const btn = e.target.closest('.cfqm-create-payment-link');
      if (!btn) return;
      const quoteId = btn.dataset.quoteId;
      btn.disabled  = true;
      btn.textContent = 'Creating…';

      $.post(AJAX_URL, {
        action:   'cfqm_create_payment_link',
        nonce:    NONCE,
        quote_id: quoteId,
      }, function (res) {
        if (res.success && res.data.link) {
          btn.replaceWith(
            '<a href="' + res.data.link + '" target="_blank" class="cfqm-btn cfqm-btn--sm cfqm-btn--success">Stripe Link ↗</a>'
          );
        } else {
          btn.disabled = false;
          btn.textContent = 'Retry';
          alert((res.data && res.data.message) || 'Could not create payment link.');
        }
      });
    });

    // Copy link
    tradesDash.addEventListener('click', function (e) {
      const btn = e.target.closest('.cfqm-copy-link');
      if (!btn) return;
      const url = btn.dataset.url;
      if (navigator.clipboard && url) {
        navigator.clipboard.writeText(url).then(function () {
          const orig = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(function () { btn.textContent = orig; }, 1500);
        });
      }
    });

    // New quote modal (open builder)
    const newQuoteBtn = document.getElementById('cfqm-btn-new-quote');
    const modal       = document.getElementById('cfqm-quote-builder-modal');
    const modalClose  = document.getElementById('cfqm-modal-close');
    const modalOverlay= document.getElementById('cfqm-modal-overlay');

    if (newQuoteBtn && modal) {
      newQuoteBtn.addEventListener('click', function () {
        modal.style.display = 'block';
        document.getElementById('cfqm-modal-body').innerHTML = '<p style="padding:20px">Loading quote builder…</p>';
        $.get(AJAX_URL, { action: 'cfqm_get_quote_builder_html', nonce: NONCE }, function (res) {
          if (res.success) {
            document.getElementById('cfqm-modal-body').innerHTML = res.data.html;
          }
        });
      });
    }
    if (modalClose && modal) modalClose.addEventListener('click', function () { modal.style.display = 'none'; });
    if (modalOverlay && modal) modalOverlay.addEventListener('click', function () { modal.style.display = 'none'; });
  }

  // ── Homeowner Query Form ──────────────────────────────────────────────

  const queryForm = document.getElementById('cfqm-query-submit-form');
  if (queryForm) {
    queryForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(queryForm);
      formData.append('action', 'cfqm_submit_query');

      const submitBtn = document.getElementById('cfqm-qf-submit');
      const spinner   = document.getElementById('cfqm-qf-spinner');
      if (submitBtn) submitBtn.disabled = true;
      if (spinner)   spinner.style.display = 'inline-block';
      hideEl('cfqm-qf-error');

      $.ajax({
        url:         AJAX_URL,
        type:        'POST',
        data:        formData,
        contentType: false,
        processData: false,
        success: function (res) {
          if (submitBtn) submitBtn.disabled = false;
          if (spinner)   spinner.style.display = 'none';
          if (res.success) {
            queryForm.style.display = 'none';
            showEl('cfqm-qf-success');
          } else {
            showAlert('cfqm-qf-error', (res.data && res.data.message) || 'Please check your details and try again.', 'error');
          }
        },
        error: function () {
          if (submitBtn) submitBtn.disabled = false;
          if (spinner)   spinner.style.display = 'none';
          showAlert('cfqm-qf-error', 'Connection error. Please try again.', 'error');
        }
      });
    });

    const resetBtn = document.getElementById('cfqm-qf-reset');
    if (resetBtn) {
      resetBtn.addEventListener('click', function (e) {
        e.preventDefault();
        queryForm.reset();
        queryForm.style.display = 'block';
        hideEl('cfqm-qf-success');
      });
    }
  }

  // ── Magic Link Form ───────────────────────────────────────────────────

  const mlForm = document.getElementById('cfqm-magic-link-request-form');
  if (mlForm) {
    mlForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const email   = document.getElementById('cfqm-ml-email');
      const spinner = document.getElementById('cfqm-ml-spinner');
      const btn     = document.getElementById('cfqm-ml-submit');

      if (!email || !email.value.trim()) {
        showAlert('cfqm-ml-error', 'Please enter your email address.', 'warning');
        return;
      }

      if (btn) btn.disabled = true;
      if (spinner) spinner.style.display = 'inline-block';
      hideEl('cfqm-ml-error');
      hideEl('cfqm-ml-success');

      $.post(AJAX_URL, {
        action:       'cfqm_send_magic_link',
        cfqm_ml_nonce: document.getElementById('cfqm_ml_nonce') ? document.getElementById('cfqm_ml_nonce').value : NONCE,
        email:         email.value.trim(),
      }, function (res) {
        if (btn) btn.disabled = false;
        if (spinner) spinner.style.display = 'none';

        if (res.success) {
          mlForm.style.display = 'none';
          showEl('cfqm-ml-success');
        } else {
          showAlert('cfqm-ml-error', (res.data && res.data.message) || 'Could not send link. Please check your email address.', 'error');
        }
      });
    });
  }

}(jQuery));
