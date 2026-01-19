(function ($) {
  // ================== כפתורים קיימים ==================
  function releaseButton($button) { setTimeout(() => { $button.removeClass('elementor-animation-active'); $button.blur(); }, 200); }
  
  // הסתרת כפתורים כפולים
  $(document).ready(function() {
    // המתן קצת כדי לוודא שהכפתורים נטענו
    setTimeout(function() {
      // הסתר כפתורים ריקים או כפולים
      $('#puzzle_check, #puzzle_save').each(function() {
        const $btn = $(this);
        // אם הכפתור ריק או לא מכיל תוכן (אבל לא אם יש לו רק אייקון)
        const html = $btn.html();
        const text = $btn.text();
        if ((!html || html.trim() === '') && (!text || text.trim() === '')) {
          $btn.hide();
        }
      });
      
      // אם יש יותר מכפתור אחד עם אותו ID, הסתר את כל האחרים חוץ מהראשון
      const checkButtons = $('#puzzle_check');
      if (checkButtons.length > 1) {
        checkButtons.slice(1).hide(); // הסתר את כל הכפתורים חוץ מהראשון
      }
      
      const saveButtons = $('#puzzle_save');
      if (saveButtons.length > 1) {
        saveButtons.slice(1).hide();
      }
    }, 100);
  });
  
  // ================== שיפורי UX - הודעות משוב משופרות ==================
  function showMessage(message, type) {
    type = type || 'info';
    
    // ניקוי HTML מסוכן
    const cleanMessage = $('<div>').text(message).html();
    
    // בדיקה אם ההודעה דורשת התחברות
    const requiresLogin = message.includes('להתחבר') || 
                         message.includes('מחובר') || 
                         message.includes('התחברות') ||
                         message.includes('הרשמה');
    
    // אייקונים לפי סוג
    const icons = {
      'success': '<span class="message-icon" aria-hidden="true">✓</span>',
      'error': '<span class="message-icon" aria-hidden="true">✗</span>',
      'info': '<span class="message-icon" aria-hidden="true">ℹ</span>',
      'warning': '<span class="message-icon" aria-hidden="true">⚠</span>'
    };
    
    const icon = icons[type] || icons['info'];
    const $container = $('.puzzle_check-container');
    
    // יצירת הודעה עם ARIA
    const messageId = 'puzzle-message-' + Date.now();
    const $message = $('<div>')
      .attr({
        'id': messageId,
        'class': type + '-message puzzle-feedback-message' + (requiresLogin ? ' requires-login-message' : ''),
        'role': 'alert',
        'aria-live': type === 'error' ? 'assertive' : 'polite',
        'aria-atomic': 'true'
      });
    
    // אם ההודעה דורשת התחברות - מבנה מיוחד עם CTA
    if (requiresLogin) {
      // קבלת URLs של התחברות והרשמה
      const loginUrl = typeof wpum_login_url !== 'undefined' ? wpum_login_url : '/התחברות/';
      const registerUrl = typeof wpum_register_url !== 'undefined' ? wpum_register_url : '/הרשמה/';
      const currentUrl = window.location.href;
      
      // בניית URLs עם redirect
      const loginUrlWithRedirect = loginUrl + (loginUrl.includes('?') ? '&' : '?') + 'redirect_to=' + encodeURIComponent(currentUrl);
      const registerUrlWithRedirect = registerUrl + (registerUrl.includes('?') ? '&' : '?') + 'redirect_to=' + encodeURIComponent(currentUrl);
      
      // עטיפת התוכן הקיים
      const $contentWrapper = $('<div class="message-content-wrapper"></div>')
        .append(icon)
        .append('<span class="message-text">' + cleanMessage + '</span>')
        .append('<button class="message-close" aria-label="סגור הודעה" title="סגור">×</button>');
      
      const $ctaButtons = $('<div class="message-cta-buttons"></div>')
        .append('<a href="' + loginUrlWithRedirect + '" class="message-cta-btn message-cta-login">התחבר עכשיו</a>')
        .append('<a href="' + registerUrlWithRedirect + '" class="message-cta-btn message-cta-register">הירשם עכשיו</a>');
      
      $message.append($contentWrapper).append($ctaButtons);
      
      // לא לסגור אוטומטית - זה חשוב מדי
      // אבל נסגור אחרי 15 שניות במקום 7
    } else {
      // הודעה רגילה - מבנה פשוט
      $message.html(icon + ' <span class="message-text">' + cleanMessage + '</span>')
        .append('<button class="message-close" aria-label="סגור הודעה" title="סגור">×</button>');
    }
    
    // הסרת הודעות קודמות
    $container.find('.puzzle-feedback-message').fadeOut(200, function() {
      $(this).remove();
    });
    
    // הוספת הודעה חדשה
    $container.html('').append($message).fadeIn(300);
    
    // סגירה אוטומטית (15 שניות אם דורש התחברות, 7 אחרת)
    const autoCloseTime = requiresLogin ? 15000 : 7000;
    const autoClose = setTimeout(() => {
      $message.fadeOut(300, function() {
        $(this).remove();
      });
    }, autoCloseTime);
    
    // סגירה ידנית
    $message.find('.message-close').on('click', function() {
      clearTimeout(autoClose);
      $message.fadeOut(300, function() {
        $(this).remove();
      });
    });
    
    // סגירה על Escape
    $(document).on('keydown.message-' + messageId, function(e) {
      if (e.key === 'Escape' && $('#' + messageId).length) {
        clearTimeout(autoClose);
        $('#' + messageId).fadeOut(300, function() {
          $(this).remove();
        });
        $(document).off('keydown.message-' + messageId);
      }
    });
    
    // אם דורש התחברות - הצג גם modal/popup
    if (requiresLogin) {
      showLoginModal(loginUrlWithRedirect, registerUrlWithRedirect);
    }
  }
  
  // ================== Modal להתחברות/הרשמה ==================
  function showLoginModal(loginUrl, registerUrl) {
    // בדיקה אם כבר יש modal פתוח
    if ($('#login-required-modal').length) {
      return;
    }
    
    // יצירת modal
    const $modal = $('<div id="login-required-modal" class="login-required-modal" role="dialog" aria-labelledby="login-modal-title" aria-modal="true"></div>');
    const $overlay = $('<div class="login-modal-overlay"></div>');
    const $content = $('<div class="login-modal-content"></div>');
    
    $content.html(`
      <button class="login-modal-close" aria-label="סגור חלון">×</button>
      <div class="login-modal-header">
        <div class="login-modal-icon-wrapper">
          <svg class="login-modal-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" fill="#DB8A16"/>
          </svg>
        </div>
        <h2 id="login-modal-title" class="login-modal-title">התחבר כדי להמשיך</h2>
        <p class="login-modal-subtitle">יש לך כבר חשבון? התחבר או הירשם כדי לשמור את ההתקדמות שלך</p>
      </div>
      <div class="login-modal-body">
        <div class="login-modal-buttons">
          <a href="${loginUrl}" class="login-modal-btn login-modal-btn-primary">
            <span>התחבר</span>
          </a>
          <a href="${registerUrl}" class="login-modal-btn login-modal-btn-secondary">
            <span>הירשם</span>
          </a>
        </div>
        <div class="login-modal-benefits">
          <h3>למה להתחבר?</h3>
          <ul>
            <li>שמירת התקדמות אוטומטית</li>
            <li>גישה לכל התשבצים</li>
            <li>תשבץ שבועי מקורי</li>
            <li>כלי עזרים מתקדמים</li>
          </ul>
        </div>
      </div>
    `);
    
    $modal.append($overlay).append($content);
    $('body').append($modal);
    
    // אנימציה כניסה
    setTimeout(() => {
      $modal.addClass('active');
    }, 10);
    
    // סגירה
    $modal.find('.login-modal-close, .login-modal-overlay').on('click', function(e) {
      if (e.target === this) {
        $modal.removeClass('active');
        setTimeout(() => {
          $modal.remove();
        }, 300);
      }
    });
    
    // סגירה על Escape
    $(document).on('keydown.login-modal', function(e) {
      if (e.key === 'Escape' && $('#login-required-modal').length) {
        $('#login-required-modal').removeClass('active');
        setTimeout(() => {
          $('#login-required-modal').remove();
        }, 300);
        $(document).off('keydown.login-modal');
      }
    });
    
    // מניעת גלילה של body כש-modal פתוח
    $('body').addClass('modal-open');
    $modal.on('remove', function() {
      $('body').removeClass('modal-open');
    });
  }

  $(document).on('click', '#puzzle_check', function (event) {
    event.preventDefault();
    const $button = $(this);
    // שמור את ה-HTML המקורי (כולל האייקון) ולא רק את הטקסט
    const originalHTML = $button.html();
    
    // איסוף כל הערכים מהטופס ידנית (כי serialize לפעמים לא עובד טוב עם עברית)
    // חשוב: לשלוח גם תאים ריקים כדי שהשרת יוכל לבדוק אותם
    const formData = {};
    $('#puzzle_form input[type="text"].cel_letter').each(function() {
      const $input = $(this);
      const name = $input.attr('name');
      const value = $input.val().trim();
      if (name) {
        // שלח גם תאים ריקים - השרת יחליט מה לבדוק
        formData[name] = value || '';
      }
    });
    
    // המרה ל-URL encoded string
    const formString = $.param(formData);
    
    $.ajax({
      url: ajaxpagination.ajaxurl, type: 'post',
      data: {
        action: 'tpuzzle_check',
        query_vars: ajaxpagination.query_vars,
        the_form: formString,
        size_x: $('#size_x').val(),
        size_y: $('#size_y').val(),
        the_post_id: $('#the_post_id').val()
      },
      beforeSend: function() {
        $button.prop({
          'disabled': true,
          'aria-busy': 'true'
        }).html('<span class="loading-spinner" aria-hidden="true"></span><span class="button-text">בודק...</span>');
        $button.addClass('is-loading');
      },
      success: function (html) { 
        $('.puzzle_check-container').html(html);
        if (html.includes('זהה')) {
          showMessage(html, 'success');
        } else {
          showMessage(html, 'error');
        }
        releaseButton($button);
        // שחזר את ה-HTML המקורי (כולל האייקון)
        $button.prop({
          'disabled': false,
          'aria-busy': 'false'
        }).removeClass('is-loading').html(originalHTML || 'בדיקה');
      },
      error: function (request) { 
        const errorMsg = request.responseText || 'שגיאה לא ידועה';
        showMessage('שגיאה בבדיקה: ' + errorMsg, 'error');
        releaseButton($button);
        // שחזר את ה-HTML המקורי (כולל האייקון)
        $button.prop({
          'disabled': false,
          'aria-busy': 'false'
        }).removeClass('is-loading').html(originalHTML || 'בדיקה');
      }
    });
  });

  $(document).on('click', '#puzzle_save', function (event) {
    event.preventDefault();
    const $button = $(this);
    const originalText = $button.text();
    
    // איסוף כל הערכים מהטופס ידנית (כי serialize לפעמים לא עובד טוב עם עברית)
    const formData = {};
    $('#puzzle_form input[type="text"].cel_letter').each(function() {
      const $input = $(this);
      const name = $input.attr('name');
      const value = $input.val().trim();
      if (name) { // גם תאים ריקים - לשמירה
        formData[name] = value || '';
      }
    });
    
    // המרה ל-URL encoded string
    const formString = $.param(formData);
    
    $.ajax({
      url: ajaxpagination.ajaxurl, type: 'post',
      data: {
        action: 'tpuzzle_save',
        query_vars: ajaxpagination.query_vars,
        the_form: formString,
        size_x: $('#size_x').val(),
        size_y: $('#size_y').val(),
        the_post_id: $('#the_post_id').val()
      },
      beforeSend: function() {
        $button.prop({
          'disabled': true,
          'aria-busy': 'true'
        }).html('<span class="loading-spinner" aria-hidden="true"></span><span class="button-text">שומר...</span>');
        $button.addClass('is-loading');
      },
      success: function (html) { 
        $('.puzzle_check-container').html(html);
        showMessage(html, 'success');
        releaseButton($button);
        $button.prop({
          'disabled': false,
          'aria-busy': 'false'
        }).removeClass('is-loading').text(originalText);
      },
      error: function (request) { 
        const errorMsg = request.responseText || 'שגיאה לא ידועה';
        showMessage('שגיאה בשמירה: ' + errorMsg, 'error');
        releaseButton($button);
        $button.prop({
          'disabled': false,
          'aria-busy': 'false'
        }).removeClass('is-loading').text(originalText);
      }
    });
  });

  $(document).on('click', '#print-crossword', function (e) { e.preventDefault(); const $button = $(this); window.print(); releaseButton($button); });
  $(document).on('click', '#show_explanations', function (event) { event.preventDefault(); const $button = $(this); $('.solution-exp_h').toggleClass('visible'); releaseButton($button); });
  $(document).on('click', '#puzzle_save-disabled', function (event) { 
    event.preventDefault(); 
    const $button = $(this); 
    showMessage('לא ניתן לשמור תשבץ דוגמה. יש להתחבר כדי לשמור התקדמות.', 'warning');
    releaseButton($button); 
  });
  
  // ================== Auto-save (שמירה אוטומטית) ==================
  let autoSaveTimer = null;
  let lastSavedData = null;
  
  function triggerAutoSave() {
    // בדיקה אם המשתמש מחובר
    if (!$('#puzzle_save').length || $('#puzzle_save').hasClass('disabled') || $('#puzzle_save-disabled').length) {
      return; // אין אפשרות לשמור
    }
    
    // איסוף הנתונים הנוכחיים
    const formData = {};
    $('#puzzle_form input[type="text"].cel_letter').each(function() {
      const $input = $(this);
      const name = $input.attr('name');
      const value = $input.val().trim();
      if (name) {
        formData[name] = value || '';
      }
    });
    
    const formString = $.param(formData);
    
    // בדיקה אם יש שינוי
    if (formString === lastSavedData) {
      return; // אין שינוי
    }
    
    lastSavedData = formString;
    
    // שמירה שקטה (ללא הודעה בולטת)
    $.ajax({
      url: ajaxpagination.ajaxurl,
      type: 'post',
      data: {
        action: 'tpuzzle_save',
        query_vars: ajaxpagination.query_vars,
        the_form: formString,
        size_x: $('#size_x').val(),
        size_y: $('#size_y').val(),
        the_post_id: $('#the_post_id').val()
      },
      success: function(html) {
        // עדכון שקט - רק אם יש הודעה
        if (html && html.trim()) {
          const $container = $('.puzzle_check-container');
          if (!$container.find('.puzzle-feedback-message').length) {
            // רק אם אין הודעה אחרת - נוסיף הודעה קטנה
            const $autoSave = $('<div class="autosave-indicator" aria-live="polite" aria-atomic="true">נשמר אוטומטית</div>');
            $container.append($autoSave);
            setTimeout(() => {
              $autoSave.fadeOut(300, function() {
                $(this).remove();
              });
            }, 2000);
          }
        }
      },
      error: function() {
        // שגיאה בשמירה אוטומטית - לא להציג למשתמש (לא קריטי)
        console.log('Auto-save failed (non-critical)');
      }
    });
  }
  
  // שמירה אוטומטית כל 30 שניות אחרי שינוי
  $(document).on('input', '.cel_letter', function() {
    // איפוס הטיימר
    if (autoSaveTimer) {
      clearTimeout(autoSaveTimer);
    }
    
    // שמירה אוטומטית אחרי 30 שניות של אי-פעילות
    autoSaveTimer = setTimeout(function() {
      triggerAutoSave();
    }, 30000); // 30 שניות
  });
  
  // שמירה אוטומטית לפני סגירת העמוד
  $(window).on('beforeunload', function() {
    if (autoSaveTimer) {
      clearTimeout(autoSaveTimer);
    }
    triggerAutoSave();
  });
  
  // ================== שיפורי מובייל ==================
  
  // מניעת זום אוטומטי במובייל
  $(document).on('focus', '.cel_letter', function() {
    if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
      const $input = $(this);
      // המתן קצת ואז focus (פותר בעיית zoom)
      setTimeout(function() {
        $input.focus();
      }, 100);
    }
  });
  
  // שיפור touch targets במובייל
  if ('ontouchstart' in window) {
    $(document).ready(function() {
      $('.puzzle_cel_letter input').css({
        'min-height': '44px',
        'min-width': '44px'
      });
    });
  }
  
  // ================== שיפורי נגישות ==================
  
  // הוספת ARIA labels לכפתורים
  $(document).ready(function() {
    $('#puzzle_check').attr({
      'aria-label': 'בדוק את התשובות',
      'title': 'בדוק את התשובות (Ctrl+Enter)'
    });
    
    $('#puzzle_save').attr({
      'aria-label': 'שמור התקדמות',
      'title': 'שמור התקדמות (Ctrl+S)'
    });
    
    $('#print-crossword').attr({
      'aria-label': 'הדפס תשבץ',
      'title': 'הדפס תשבץ'
    });
    
    $('#show_explanations').attr({
      'aria-label': 'הצג הסברים',
      'title': 'הצג הסברים'
    });
  });
  
  // Keyboard shortcuts
  $(document).on('keydown', function(e) {
    // Ctrl+Enter = בדיקה
    if (e.ctrlKey && e.key === 'Enter') {
      e.preventDefault();
      $('#puzzle_check').click();
    }
    
    // Ctrl+S = שמירה
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault();
      if ($('#puzzle_save').length && !$('#puzzle_save').prop('disabled')) {
        $('#puzzle_save').click();
      }
    }
  });

  // ================== עזרי רשת ותאים ==================
  function getSize() { return { sx: parseInt($('#size_x').val(), 10), sy: parseInt($('#size_y').val(), 10) }; }
  function idxToRC(idx, sx) { const n = idx - 1; return { r: Math.floor(n / sx), c: n % sx }; }
  function rcToIdx(r, c, sx) { return r * sx + c + 1; }
  function cellByIdx(idx) { return $('.puzzle_cel_' + idx); }
  function inputByIdx(idx) { return $('#cel_letter_' + idx); }
  function hasInput(idx) { return inputByIdx(idx).length > 0; }
  function isBlack(idx) { return cellByIdx(idx).hasClass('black_cell'); }

  // ================== מצב וצביעה של ההגדרה ==================
  const state = { dir: 'across', cells: [], currentIdx: null, lastFocusedId: null };

  function clearHighlight() { $('.puzzle_cel').removeClass('active-clue active-cell'); }
  function highlightCells(indices) { clearHighlight(); indices.forEach(i => cellByIdx(i).addClass('active-clue')); }
  function highlightFocused(idx) { cellByIdx(idx).addClass('active-cell'); }

  function collectClueFrom(idx, dir) {
    const { sx, sy } = getSize();
    const { r, c } = idxToRC(idx, sx);

    // מציאת תחילת המילה
    let sr = r, sc = c;
    if (dir === 'across') {
      while (sc - 1 >= 0) {
        const k = rcToIdx(sr, sc - 1, sx);
        if (isBlack(k) || !hasInput(k)) break; sc--;
      }
    } else {
      while (sr - 1 >= 0) {
        const k = rcToIdx(sr - 1, sc, sx);
        if (isBlack(k) || !hasInput(k)) break; sr--;
      }
    }

    // איסוף קדימה
    const out = [];
    let rr = sr, cc = sc;
    while (rr >= 0 && rr < sy && cc >= 0 && cc < sx) {
      const k = rcToIdx(rr, cc, sx);
      if (isBlack(k) || !hasInput(k)) break;
      out.push(k);
      if (dir === 'across') cc++; else rr++;
    }
    return out;
  }

  function canToggleAt(idx) { return collectClueFrom(idx, 'across').length > 1 && collectClueFrom(idx, 'down').length > 1; }

  function setActiveFromInput($input, preferDir) {
    const id = $input.attr('id'); if (!id) return;
    const idx = parseInt(id.replace('cel_letter_', ''), 10);
    let dir = preferDir || state.dir;
    let cells = collectClueFrom(idx, dir);
    if (cells.length <= 1) { dir = (dir === 'across' ? 'down' : 'across'); cells = collectClueFrom(idx, dir); }
    state.dir = dir; state.cells = cells; state.currentIdx = idx;
    highlightCells(cells); highlightFocused(idx);
  }

  function moveWithinActive(fromIdx, step) {
    const i = state.cells.indexOf(fromIdx);
    if (i === -1) return;
    const j = i + step;
    if (j < 0 || j >= state.cells.length) return;
    inputByIdx(state.cells[j]).focus().select();
  }

  // תנועה גאומטרית – מאוזן: ימינה/שמאלה
  function moveLeftRight(fromIdx, dC) {
    const { sx } = getSize();
    let { r, c } = idxToRC(fromIdx, sx);
    c += dC;
    while (c >= 0 && c < sx) {
      const k = rcToIdx(r, c, sx);
      if (!isBlack(k) && hasInput(k)) { inputByIdx(k).focus().select(); return; }
      c += dC;
    }
  }

  // תנועה מאונך – למעלה/למטה
  function moveUpDown(fromIdx, dR) {
    const { sx, sy } = getSize();
    let { r, c } = idxToRC(fromIdx, sx);
    r += dR;
    while (r >= 0 && r < sy) {
      const k = rcToIdx(r, c, sx);
      if (!isBlack(k) && hasInput(k)) { inputByIdx(k).focus().select(); return; }
      r += dR;
    }
  }

  // ================== התנהגות קלט וניווט ==================
  let lastValue = '';

  // הדגשת ההגדרה כשנכנסים לתא
  $(document).on('focus', '.cel_letter', function () { lastValue = $(this).val(); setActiveFromInput($(this), null); });

  // קליק נוסף באותו תא → החלפת כיוון אם אפשר
  $(document).on('mousedown', '.cel_letter', function () {
    const $inp = $(this);
    if (state.lastFocusedId === $inp.attr('id')) {
      const idx = parseInt($inp.attr('id').replace('cel_letter_', ''), 10);
      if (canToggleAt(idx)) setActiveFromInput($inp, state.dir === 'across' ? 'down' : 'across');
    }
  });
  $(document).on('focusin', '.cel_letter', function () { state.lastFocusedId = $(this).attr('id'); });

  // אחרי הקלדה – קדימה בתוך ההגדרה; Backspace על ריק – אחורה
  $(document).on('input', '.cel_letter', function () {
    const $input = $(this);
    const val = $input.val();
    const idx = parseInt($input.attr('id').replace('cel_letter_', ''), 10);
    if (val && val.length === 1 && val !== lastValue) moveWithinActive(idx, +1);
    lastValue = val;
  });

  // ===== חיצים – גאומטרי =====
  $(document).on('keydown', '.cel_letter', function (e) {
    const $input = $(this);
    const idx = parseInt($input.attr('id').replace('cel_letter_', ''), 10);

    // Backspace: אם ריק – אחורה בהגדרה
    if (e.key === 'Backspace') {
      if (!$input.val()) { e.preventDefault(); moveWithinActive(idx, -1); }
      return;
    }

    if (e.key === 'ArrowRight') { e.preventDefault(); moveLeftRight(idx, +1); return; }
    if (e.key === 'ArrowLeft')  { e.preventDefault(); moveLeftRight(idx, -1); return; }
    if (e.key === 'ArrowUp')    { e.preventDefault(); moveUpDown(idx, -1);   return; }
    if (e.key === 'ArrowDown')  { e.preventDefault(); moveUpDown(idx, +1);   return; }

    if (e.key === 'Tab') { e.preventDefault(); moveWithinActive(idx, e.shiftKey ? -1 : +1); return; }
    if (e.key === ' ' || e.code === 'Space') { e.preventDefault(); if (canToggleAt(idx)) setActiveFromInput($input, state.dir === 'across' ? 'down' : 'across'); }
  });

  // ================== הדפסה: כותרת רק בהדפסה ==================
  function ensurePrintTitle() {
    const $root = $('.crossword_element').first();
    if (!$root.length) return;
    if ($root.find('.cw-print-title').length) return;
    const candidates = [
      $('.elementor-widget-heading h1:visible:first').text(),
      $('.elementor-widget-heading h2:visible:first').text(),
      $('h1.entry-title:visible:first').text(),
      document.title || ''
    ].filter(Boolean);
    const title = candidates[0] || 'תשבץ';
    const $title = $('<div class="cw-print-title" aria-hidden="true"></div>').text(title);
    $root.prepend($title);
  }
  $(document).ready(ensurePrintTitle);
  setTimeout(ensurePrintTitle, 800);

  // ================== CSS (כולל תיקון מרכז/ריווח בהדפסה) ==================
  (function injectStyles() {
    if (document.getElementById('hb-crossword-styles')) return;

    const PUZZLE_SCALE = 1.12; // גודל הלוח בהדפסה

    const css = `
.cw-print-title{ display:none; }

/* הדגשה במסך */
.puzzle_cel.active-clue{outline:2px solid #f0b400;background:#fff6d6}
.puzzle_cel.active-cell{box-shadow:inset 0 0 0 3px #ff6a00}
.puzzle_cel.black_cell{background:#111!important}

/* --- הדפסה --- */
@media print{
  html, body { margin:0 !important; padding:0 !important; height:auto !important; }

  body *{ visibility:hidden !important; }
  .crossword_element, .crossword_element *{ visibility:visible !important; }

  .crossword_element{
    position:fixed !important;
    top:0; left:0; right:0;
    margin:0 auto !important;
    padding:0 !important;
    width:100% !important;
    max-width:100% !important;
  }

  .cw-print-title{
    display:block !important;
    text-align:center !important;
    font-size:18pt !important;
    font-weight:700 !important;
    margin:4mm 0 4mm 0 !important;
  }

  /* ---- מרכז את הלוח בהדפסה ---- */
  .crossword_puzzle{ text-align:center !important; }
  .puzzle{
    display:inline-block !important;       /* מאפשר text-align:center על העטיפה */
    margin:0 auto 10mm auto !important;    /* ↑ ריווח גדול יותר מהלוח אל ההגדרות (≈+4 מ״מ) */
    transform: scale(${PUZZLE_SCALE});
    transform-origin: top center;
  }

  /* הגדרות – שתי עמודות זו לצד זו */
  .crossword-definitions{
    display:flex !important;
    flex-direction:row !important;
    gap:8mm !important;
    align-items:flex-start !important;
    justify-content:center !important;
    margin:0 auto !important;
    width:100% !important;
  }
  .crossword-definitions > .vertical-definitions,
  .crossword-definitions > .horizontal-definitions{
    flex:1 1 0 !important; min-width:0 !important;
  }

  .crossword-definitions h4{ margin:0 0 2mm 0 !important; font-weight:700 !important; font-size:12pt !important; }
  .crossword-definitions p{  margin:0 0 1.6mm 0 !important; line-height:1.3 !important;  font-size:10.3pt !important; }

  .puzzle_cel{border:1px solid #000!important}
  .puzzle_cel_number_val{font-weight:700!important}

  @page { size: A4 portrait; margin: 6mm; }
}
`;
    const style = document.createElement('style');
    style.id = 'hb-crossword-styles';
    style.type = 'text/css';
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
  })();

})(jQuery);


