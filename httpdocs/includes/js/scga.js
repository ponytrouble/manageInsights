
scga = {}; 
scga.i = '';
scga.confirmation = function(text, form) 
{
	$('<div></div>').html(text).dialog({ resizable: false, height:140, modal: true, buttons: {
			"Confirm": function() {
				$(this).dialog('destroy'); $(form).submit(); 
			},
			Cancel: function() {
				$(this).dialog('destroy'); return false; 
			}}
	});
}

scga.dialogConfirm = function() 
{
	$('input[value="Delete Account"]').click(function() {
		msg = 'Do you really want to delete selected account(s)?'; 
		scga.confirmation(msg, $(this).parent('form')); return false; 
	});
	$('input[value="Delete Job"]').click(function() {
		msg = 'Please confirm deleting job.'; 
		scga.confirmation(msg, $(this).parent('form')); return false; 
	});
	$('input[value="Delete Silo"]').click(function() {
		msg = 'Please confirm deleting silo.'; 
		scga.confirmation(msg, $(this).parent('form')); return false; 
	});
	$('input[value="Load File"]').click(function() {
		uri = $('select[name=report] option:selected').val();
		$('#reports pre').load(uri);
		return false; 
	});
}

scga.showProgress = function() {
    scga.i = setInterval(function() { 
        $.getJSON('includes/js/progress.js?no_cache=' + (new Date()).getTime(), function(data) {
            if(data == null) {
                clearInterval(scga.i);
                window.location = window.location.href;
                return;
            }
			var count = (parseInt(data.countStarting) - parseInt(data.countRemaining));
			var total = Math.min(data.countStarting, data.countRunMaxium);
			var percentage = Math.floor(100 * count / total); 
			console.log([count, total, percentage]); console.log(data);
            if(percentage < 0 || percentage > 99) {
                window.location = window.location.href;
            }
            $("#progressbar").progressbar({ value: percentage });
			$('#reports tt.countRunMaxium').html(data.countRunMaxium); 
			$('#reports tt.countBatched').html(data.countBatched); 
			$('#reports tt.countRemaining').html(data.countRemaining); 
        });
    }, 400);
}