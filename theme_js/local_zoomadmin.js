/*
* Incluir este arquivo na pasta javascript do tema
* e adicionar o seguinte código ao arquivo config.php do tema:
* $THEME->javascripts_footer = array('local_zoomadmin');
*/
require(['jquery'], function($) {
  $('a[data-uuid][data-filetype="MP4"]').click(function() {
    let href = $('a[data-title="profile,moodle"]').attr('href');
    let userid = href.substring(href.indexOf('id=') + 3);

    $.ajax(
      '../../webservice/rest/server.php?' +
      'wstoken=a4f6103b7a46cc1f70ca8fa304b49fd1' +
      '&wsfunction=local_zoomadmin_insert_recording_participant' +
      '&uuid=' + $(this).attr('data-uuid') +
      '&userid=' + userid,
      {
      success: function(response) {
        console.info(response);
      }
    });
  });
});
