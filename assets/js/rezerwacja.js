(function($){
    $(document).on('submit','#wressla-rez-form',function(e){
        e.preventDefault();
        var $f = $(this);
        var $s = $f.find('.wressla-rez-status');
        $s.text('…');
        $.post(WRESSLA_REZ.ajax, $f.serialize())
            .done(function(resp){
                if(resp && resp.success){
                    if (resp.data && resp.data.redirect){
                        window.location.href = resp.data.redirect;
                        return;
                    }
                    $s.html(WRESSLA_REZ.ok);
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
