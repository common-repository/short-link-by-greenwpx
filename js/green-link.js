

function copyText(text) {
  const el = document.createElement('textarea');
  el.value = text;
  document.body.appendChild(el);
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);

  alert("Link copied successfully");
}

jQuery(document).ready(function ($) {
  // Attach a click event handler to links with a specific class

  $('#create-link-form1').submit(function (e) {
    e.preventDefault();

    var form = $(this);
    var formData = form.serialize();

    $.ajax({
      url: form.attr('action'),
      type: 'POST',
      data: formData,
      success: function (response) {
        console.log(response);
        if(response == 1){
          alert("Link created successfully");
          // Redirect to the Short Links table
          window.location.href = "?page=green-short-links";
        } else {
          alert("Link already axits. Please choose a different one.");
        }
        
      },
      error: function () {
        alert("Error occurred while creating the link");
      }
    });
  });

});