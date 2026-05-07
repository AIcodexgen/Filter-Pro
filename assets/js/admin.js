/* Everything Filter — Admin JS */
jQuery(function ($) {

  /* ── Toast ── */
  function toast(msg, type) {
    type = type || 'success';
    var t = $('<div class="ef-toast ef-toast-' + type + '">' + msg + '</div>');
    $('body').append(t);
    setTimeout(function () {
      t.css('animation', 'ef-toast-out .3s ease forwards');
      setTimeout(function () { t.remove(); }, 300);
    }, 3000);
  }

  /* ── AJAX helper ── */
  function efAjax(action, data, btn, onSuccess) {
    var $btn = $(btn);
    var $text = $btn.find('.ef-btn-text');
    var $spin = $btn.find('.ef-spinner');
    $btn.prop('disabled', true);
    $text.hide(); $spin.show();

    $.post(EF.ajaxurl, $.extend({ action: action, nonce: EF.nonce }, data), function (res) {
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
  var $attForm = $('#ef-attorney-form');
  if ($attForm.length) {

    /* Submit */
    $attForm.on('submit', function (e) {
      e.preventDefault();
      efAjax('ef_save_attorney', $attForm.serialize(), '#ef-save-btn', function (data) {
        if (data.redirect) {
          setTimeout(function () { window.location = data.redirect; }, 800);
        }
      });
    });

    /* Add combo row */
    $('#ef-add-combo').on('click', function () {
      var tpl = document.querySelector('#ef-combo-template');
      var clone = tpl.content.cloneNode(true);
      $('#ef-combos-list').append(clone);
      bindComboHandlers();
    });

    /* Remove combo row */
    function bindComboHandlers() {
      $('#ef-combos-list').off('click', '.ef-remove-combo').on('click', '.ef-remove-combo', function () {
        $(this).closest('.ef-combo-row').fadeOut(200, function () { $(this).remove(); });
      });

      /* Custom practice/state option */
      $('#ef-combos-list').off('change', 'select').on('change', 'select', function () {
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
    $('#ef-upload-photo, #ef-photo-preview').on('click', function (e) {
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
        $('#ef-image-url').val(att.url);
        var $preview = $('#ef-photo-preview');
        $preview.html('<img src="' + att.url + '" id="ef-photo-img" alt="">');
        $('#ef-remove-photo').show();
      });
      mediaFrame.open();
    });

    $(document).on('click', '#ef-remove-photo', function () {
      $('#ef-image-url').val('');
      $('#ef-photo-preview').html('<div class="ef-photo-placeholder" id="ef-photo-img"><span class="dashicons dashicons-format-image"></span><p>No photo yet</p></div>');
      $(this).hide();
    });
  }

  /* ════════════════════════════════════════
   * ATTORNEYS LIST — delete + sort
   * ════════════════════════════════════════ */
  /* Delete */
  $(document).on('click', '.ef-delete-attorney', function () {
    if (!confirm('Delete this attorney? This also removes all their practice/state combinations. This cannot be undone.')) return;
    var $btn = $(this);
    var id = $btn.data('id');
    $.post(EF.ajaxurl, { action: 'ef_delete_attorney', nonce: EF.nonce, id: id }, function (res) {
      if (res.success) { $btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); toast('Attorney deleted.'); }
      else toast('Could not delete.', 'error');
    });
  });

  /* Drag-to-sort */
  if ($('#ef-sortable').length) {
    $('#ef-sortable').sortable({
      handle: '.ef-drag-handle',
      placeholder: 'ef-sortable-placeholder',
      update: function () {
        var order = [];
        $('#ef-sortable tr').each(function () { order.push($(this).data('id')); });
        $.post(EF.ajaxurl, { action: 'ef_reorder_attorneys', nonce: EF.nonce, order: order });
      }
    });
  }

  /* ════════════════════════════════════════
   * OFFICES
   * ════════════════════════════════════════ */
  var $officeForm = $('#ef-office-form');
  if ($officeForm.length) {

    $officeForm.on('submit', function (e) {
      e.preventDefault();
      efAjax('ef_save_office', $officeForm.serialize(), $officeForm.find('[type=submit]'), function () {
        setTimeout(function () { location.reload(); }, 800);
      });
    });

    /* Edit button populates form */
    $(document).on('click', '.ef-edit-office', function () {
      var $b = $(this);
      $('#ef-office-id').val($b.data('id'));
      $('#ef-office-name').val($b.data('name'));
      $('#ef-office-address').val($b.data('address'));
      $('#ef-office-active').prop('checked', $b.data('active') == 1);
      $('#ef-office-form-title').text('Edit Office');
      $('#ef-office-cancel').show();
      $('html,body').animate({ scrollTop: $('#ef-office-form').offset().top - 80 }, 300);
    });

    $('#ef-office-cancel').on('click', function () {
      $officeForm[0].reset();
      $('#ef-office-id').val('');
      $('#ef-office-form-title').text('Add Office');
      $(this).hide();
    });

    /* Delete office */
    $(document).on('click', '.ef-delete-office', function () {
      if (!confirm('Delete this office?')) return;
      var id = $(this).data('id');
      $.post(EF.ajaxurl, { action: 'ef_delete_office', nonce: EF.nonce, id: id }, function (res) {
        if (res.success) { location.reload(); }
        else toast('Could not delete.', 'error');
      });
    });
  }

  /* ════════════════════════════════════════
   * APPEARANCE / SETTINGS
   * ════════════════════════════════════════ */
  var $settingsForm = $('#ef-settings-form');
  if ($settingsForm.length) {

    /* Color pickers */
    $('.ef-color-picker').wpColorPicker({
      change: function () { /* live preview could go here */ },
      clear: function () {}
    });

    /* Border radius range */
    var $range = $('#ef-radius-range');
    var $val   = $('#ef-radius-val');
    $range.on('input', function () { $val.text($(this).val() + 'px'); });

    /* Color presets */
    $('.ef-preset').on('click', function () {
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

      efAjax('ef_save_settings', data, '#ef-settings-save', null);
    });

    /* Reset to defaults */
    $('#ef-settings-reset').on('click', function () {
      if (!confirm('Reset all appearance settings to defaults?')) return;
      $.post(EF.ajaxurl, { action: 'ef_save_settings', nonce: EF.nonce, _reset: '1' }, function (res) {
        if (res.success) { toast('Settings reset.'); setTimeout(function () { location.reload(); }, 800); }
      });
    });
  }

});
