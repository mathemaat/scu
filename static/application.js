$(function()
{
  if ($("#alerts").length)
  {
    var context = $('#alerts');
    
    $('.alert.fade-out', context).each(function() {
      $(this).delay(10000).fadeOut(2000);
    });
  }
});
