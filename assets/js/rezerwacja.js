(function($){
    $(document).on('submit','#wressla-rez-form',function(e){
        e.preventDefault();
        var $f = $(this);
        var $s = $f.find('.wressla-rez-status');
        $s.text('…');
        
        // If there is a selected slot radio, split into date/time
        var $slot = $('input[name="slot"]:checked');
        if ($slot.length) {
            var val = $slot.val() || '';
            var parts = val.split(/\s+/);
            if (parts.length >= 2) {
                $f.find('input[name="date"]').val(parts[0]);
                $f.find('input[name="time"]').val(parts[1]);
            }
        }
        $.post(WRESSLA_REZ.ajax, $f.serialize())

            .done(function(resp){
                if(resp && resp.success){
                    $s.html(WRESSLA_REZ.ok + (resp.data && resp.data.gcal ? ' <a target="_blank" rel="noopener" href="'+resp.data.gcal+'">Dodaj do Google Calendar</a>' : ''));
                    $f[0].reset();
                } else {
                    $s.text((resp && resp.data && resp.data.message) || WRESSLA_REZ.fail);
                }
            })
            .fail(function(){
                $s.text(WRESSLA_REZ.fail);
            });
    });
})(jQuery);
