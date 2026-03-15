$(document).ready(function() {
	/* Edit user personal info */
	$("#edit_user").submit(function(e){
		e.preventDefault();
		var fd = new FormData(this);
		fd.append("is_ajax", 1);
		fd.append("type", "edit_record");
		fd.append("form", $(this).attr('name'));
		$.ajax({
			url: $(this).attr('action'),
			type: "POST",
			data: fd,
			dataType: "json",
			contentType: false,
			cache: false,
			processData: false,
			success: function(data) {
				if (data.error && data.error !== '') {
					toastr.error(data.error);
				} else {
					toastr.success(data.result || 'Updated successfully');
				}
				if (data.csrf_hash) {
					$('input[name="csrf_token"]').val(data.csrf_hash);
				}
				try { Ladda.stopAll(); } catch(ex){}
			},
			error: function(xhr, status, err) {
				var msg = 'Save failed'; try { var r = JSON.parse(xhr.responseText); if(r.error) msg = r.error; } catch(ex){ msg = xhr.status + ': ' + (err || status); } toastr.error(msg);
				try { Ladda.stopAll(); } catch(ex){}
			}
		});
	});

	/* Update profile photo */
	$("#ci_logo").submit(function(e){
		e.preventDefault();
		var fd = new FormData(this);
		fd.append("is_ajax", 1);
		fd.append("type", "edit_record");
		fd.append("form", $(this).attr('name'));
		$.ajax({
			url: $(this).attr('action'),
			type: "POST",
			data: fd,
			dataType: "json",
			contentType: false,
			cache: false,
			processData: false,
			success: function(data) {
				if (data.error && data.error !== '') {
					toastr.error(data.error);
				} else {
					toastr.success(data.result || 'Photo updated');
					setTimeout(function(){ window.location.reload(); }, 1500);
				}
				if (data.csrf_hash) {
					$('input[name="csrf_token"]').val(data.csrf_hash);
				}
				try { Ladda.stopAll(); } catch(ex){}
			},
			error: function(xhr, status, err) {
				var msg = 'Upload failed'; try { var r = JSON.parse(xhr.responseText); if(r.error) msg = r.error; } catch(ex){ msg = xhr.status + ': ' + (err || status); } toastr.error(msg);
				try { Ladda.stopAll(); } catch(ex){}
			}
		});
	});

	/* Show selected filename */
	$('.custom-file-input').on('change', function(){
		var fileName = $(this).val().split('\\').pop();
		$(this).next('.custom-file-label').html(fileName || 'Choose file...');
	});
});
