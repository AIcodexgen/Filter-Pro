/* Attorney Directory Pro — Admin JS */
jQuery(function ($) {

  /* ── Toast ── */
  function toast(msg, type) {
    type = type || 'success';
    var t = $('<div class="afp-toast afp-toast-' + type + '">' + msg + '</div>');
    $('body').append(t);
    setTimeout(function () {
      t.css('animation', 'afp-toast-out .3s ease forwards');
      setTimeout(function () { t.remove(); }, 300);
    }, 3000);
  }

  /* ── AJAX helper ── */
  function afpAjax(action, data, btn, onSuccess) {
    var $btn = $(btn);
    var $text = $btn.find('.afp-btn-text');
    var $spin = $btn.find('.afp-spinner');
    $btn.prop('disabled', true);
    $text.hide(); $spin.show();

    $.post(AFP.ajaxurl, $.extend({ action: action, nonce: AFP.nonce }, data), function (res) {
      $btn.prop('disabled', false); $text.show(); $spin.hide();
      if (res.success) {
        toast(res.data.message || 'Saved!', 'success');
        if (onSuccess) onSuccess(res.data);
      } else {
        toast(res.data.message || 'Error. Please try again.', 'error');
      }
    }).fail(function () {
      $btn.prop('disabled', false); $text.show(); $spin.hide();
      toast('Server error. Please try again.', 'error');
    });
  }

  /* ════════════════════════════════════════
   * ATTORNEY FORM
   * ════════════════════════════════════════ */
  var $attForm = $('#afp-attorney-form');
  if ($attForm.length) {

    /* Submit */
    $attForm.on('submit', function (e) {
      e.preventDefault();
      afpAjax('afp_save_attorney', $attForm.serialize(), '#afp-save-btn', function (data) {
        if (data.redirect) {
          setTimeout(function () { window.location = data.redirect; }, 800);
        }
      });
    });

    /* Add combo row */
    $('#afp-add-combo').on('click', function () {
      var tpl = document.querySelector('#afp-combo-template');
      var clone = tpl.content.cloneNode(true);
      $('#afp-combos-list').append(clone);
      bindComboHandlers();
    });

    /* Remove combo row */
    function bindComboHandlers() {
      $('#afp-combos-list').off('click', '.afp-remove-combo').on('click', '.afp-remove-combo', function () {
        $(this).closest('.afp-combo-row').fadeOut(200, function () { $(this).remove(); });
      });

      /* Custom practice/state option */
      $('#afp-combos-list').off('change', 'select').on('change', 'select', function () {
        if ($(this).val() === '__custom__') {
          var custom = prompt('Enter custom value:');
          if (custom && custom.trim()) {
            var opt = new Option(custom.trim(), custom.trim(), true, true);
            $(this).find('option[value="__custom__"]').before(opt);
            $(this).val(custom.trim());
          } else {
            $(this).val('');
          }
        }
      });
    }
    bindComboHandlers();

    /* Photo uploader */
    var mediaFrame;
    $('#afp-upload-photo, #afp-photo-preview').on('click', function (e) {
      e.preventDefault();
      if (mediaFrame) { mediaFrame.open(); return; }
      mediaFrame = wp.media({
        title: 'Choose Attorney Photo',
        button: { text: 'Use This Photo' },
        multiple: false,
        library: { type: 'image' }
      });
      mediaFrame.on('select', function () {
        var att = mediaFrame.state().get('selection').first().toJSON();
        $('#afp-image-url').val(att.url);
        var $preview = $('#afp-photo-preview');
        $preview.html('<img src="' + att.url + '" id="afp-photo-img" alt="">');
        $('#afp-remove-photo').show();
      });
      mediaFrame.open();
    });

    $(document).on('click', '#afp-remove-photo', function () {
      $('#afp-image-url').val('');
      $('#afp-photo-preview').html('<div class="afp-photo-placeholder" id="afp-photo-img"><span class="dashicons dashicons-format-image"></span><p>No photo yet</p></div>');
      $(this).hide();
    });
  }

  /* ════════════════════════════════════════
   * ATTORNEYS LIST — delete + sort
   * ════════════════════════════════════════ */
  /* Delete */
  $(document).on('click', '.afp-delete-attorney', function () {
    if (!confirm('Delete this attorney? This also removes all their practice/state combinations. This cannot be undone.')) return;
    var $btn = $(this);
    var id = $btn.data('id');
    $.post(AFP.ajaxurl, { action: 'afp_delete_attorney', nonce: AFP.nonce, id: id }, function (res) {
      if (res.success) { $btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); toast('Attorney deleted.'); }
      else toast('Could not delete.', 'error');
    });
  });

  /* Drag-to-sort */
  if ($('#afp-sortable').length) {
    $('#afp-sortable').sortable({
      handle: '.afp-drag-handle',
      placeholder: 'afp-sortable-placeholder',
      update: function () {
        var order = [];
        $('#afp-sortable tr').each(function () { order.push($(this).data('id')); });
        $.post(AFP.ajaxurl, { action: 'afp_reorder_attorneys', nonce: AFP.nonce, order: order });
      }
    });
  }

  /* ════════════════════════════════════════
   * OFFICES
   * ════════════════════════════════════════ */
  var $officeForm = $('#afp-office-form');
  if ($officeForm.length) {

    $officeForm.on('submit', function (e) {
      e.preventDefault();
      afpAjax('afp_save_office', $officeForm.serialize(), $officeForm.find('[type=submit]'), function () {
        setTimeout(function () { location.reload(); }, 800);
      });
    });

    /* Edit button populates form */
    $(document).on('click', '.afp-edit-office', function () {
      var $b = $(this);
      $('#afp-office-id').val($b.data('id'));
      $('#afp-office-name').val($b.data('name'));
      $('#afp-office-address').val($b.data('address'));
      $('#afp-office-active').prop('checked', $b.data('active') == 1);
      $('#afp-office-form-title').text('Edit Office');
      $('#afp-office-cancel').show();
      $('html,body').animate({ scrollTop: $('#afp-office-form').offset().top - 80 }, 300);
    });

    $('#afp-office-cancel').on('click', function () {
      $officeForm[0].reset();
      $('#afp-office-id').val('');
      $('#afp-office-form-title').text('Add Office');
      $(this).hide();
    });

    /* Delete office */
    $(document).on('click', '.afp-delete-office', function () {
      if (!confirm('Delete this office?')) return;
      var id = $(this).data('id');
      $.post(AFP.ajaxurl, { action: 'afp_delete_office', nonce: AFP.nonce, id: id }, function (res) {
        if (res.success) { location.reload(); }
        else toast('Could not delete.', 'error');
      });
    });
  }

  /* ════════════════════════════════════════
   * APPEARANCE / SETTINGS
   * ════════════════════════════════════════ */
  var $settingsForm = $('#afp-settings-form');
  if ($settingsForm.length) {

    /* Color pickers */
    $('.afp-color-picker').wpColorPicker({
      change: function () { /* live preview could go here */ },
      clear: function () {}
    });

    /* Border radius range */
    var $range = $('#afp-radius-range');
    var $val   = $('#afp-radius-val');
    $range.on('input', function () { $val.text($(this).val() + 'px'); });

    /* Color presets */
    $('.afp-preset').on('click', function () {
      var $b = $(this);
      applyColorPreset('primary_color',    $b.data('primary'));
      applyColorPreset('accent_color',     $b.data('accent'));
      applyColorPreset('background_color', $b.data('bg'));
    });

    function applyColorPreset(name, color) {
      var $input = $settingsForm.find('[name="' + name + '"]');
      $input.val(color).trigger('change');
      /* Update wp-color-picker swatch */
      $input.closest('.wp-picker-container').find('.wp-color-result').css('background-color', color);
    }

    /* Save */
    $settingsForm.on('submit', function (e) {
      e.preventDefault();

      /* Collect wp-color-picker values manually */
      var data = {};
      $settingsForm.find('[name]').each(function () {
        var $el = $(this);
        var name = $el.attr('name');
        if (!name) return;
        if ($el.attr('type') === 'checkbox') {
          data[name] = $el.is(':checked') ? '1' : '0';
        } else {
          data[name] = $el.val();
        }
      });

      afpAjax('afp_save_settings', data, '#afp-settings-save', null);
    });

    /* Reset to defaults */
    $('#afp-settings-reset').on('click', function () {
      if (!confirm('Reset all appearance settings to defaults?')) return;
      $.post(AFP.ajaxurl, { action: 'afp_save_settings', nonce: AFP.nonce, _reset: '1' }, function (res) {
        if (res.success) { toast('Settings reset.'); setTimeout(function () { location.reload(); }, 800); }
      });
    });
  }

});
