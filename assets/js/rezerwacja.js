(function($){
    $(document).on('submit','#wressla-rez-form',function(e){
        e.preventDefault();
        var $f = $(this);
        var $s = $f.find('.wressla-rez-status');
        $s.text('…');
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
