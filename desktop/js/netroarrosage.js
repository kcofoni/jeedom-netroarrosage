/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

$('#bt_syncnetroarrosage').on('click', function () {
    $('#div_alert').showAlert({message: '{{Synchronisation en cours}}', level: 'warning'});
    $.ajax({
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/netroarrosage/core/ajax/netroarrosage.ajax.php",
        data: {
            action: "synchronize",
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Synchronisation réalisée avec succès}}', level: 'success'});
            setTimeout( function() {
                location.reload();
            }, 2000);
        }
    });
});

function printEqLogic(_eqLogic) {
    // affichage de l'image
    $('#img_device').attr("src", $('.eqLogicDisplayCard[data-eqLogic_id=' + _eqLogic.id + '] img').attr('src'));
    
    // inutilisé pour le moment
    var type = _eqLogic.configuration.type;

    if (type == 'NetroController' ) {
      $('.netroarrosage[data-l1key=configuration][data-l2key=version]').value(_eqLogic.configuration.version);
      $('.netroarrosage[data-l1key=configuration][data-l2key=sw_version]').value(_eqLogic.configuration.sw_version);
      $('.netroarrosage[data-l1key=configuration][data-l2key=nb_zones]').value(_eqLogic.configuration.nb_zones);
      $('.netroarrosage[data-l1key=configuration][data-l2key=name]').value(_eqLogic.configuration.name);
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_limit]').value(_eqLogic.configuration.token_limit);            
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_remaining]').value(_eqLogic.configuration.token_remaining);            


      $('.netroarrosage[data-l1key=configuration][data-l2key=version]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=sw_version]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=nb_zones]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=battery_level]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=id]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=smart]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=name]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_limit]').closest('.form-group').show();      
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_remaining]').closest('.form-group').show();            
    }

    if (type == 'NetroSensor' ) {
      $('.netroarrosage[data-l1key=configuration][data-l2key=version]').value(_eqLogic.configuration.version);
      $('.netroarrosage[data-l1key=configuration][data-l2key=sw_version]').value(_eqLogic.configuration.sw_version);
      $('.netroarrosage[data-l1key=configuration][data-l2key=name]').value(_eqLogic.configuration.name);
      $('.netroarrosage[data-l1key=configuration][data-l2key=battery_level]').value(_eqLogic.configuration.battery_level + ' %');

      $('.netroarrosage[data-l1key=configuration][data-l2key=nb_zones]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=id]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=smart]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=battery_level]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_limit]').closest('.form-group').hide();      
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_remaining]').closest('.form-group').hide();                   
    }

    if (type == 'NetroZone' ) {
      $('.netroarrosage[data-l1key=configuration][data-l2key=version]').value(_eqLogic.configuration.version);
      $('.netroarrosage[data-l1key=configuration][data-l2key=sw_version]').value(_eqLogic.configuration.sw_version);
      $('.netroarrosage[data-l1key=configuration][data-l2key=id]').value(_eqLogic.configuration.id);
      $('.netroarrosage[data-l1key=configuration][data-l2key=name]').value(_eqLogic.configuration.name);                  
      $('.netroarrosage[data-l1key=configuration][data-l2key=smart]').value(_eqLogic.configuration.smart);

      $('.netroarrosage[data-l1key=configuration][data-l2key=version]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=sw_version]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=nb_zones]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=battery_level]').closest('.form-group').hide();
      $('.netroarrosage[data-l1key=configuration][data-l2key=id]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=smart]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=name]').closest('.form-group').show();
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_limit]').closest('.form-group').hide();      
      $('.netroarrosage[data-l1key=configuration][data-l2key=token_remaining]').closest('.form-group').hide();                   
    }
}

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = {configuration: {}}
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
  tr += '<option value="">{{Aucune}}</option>'
  tr += '</select>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
  tr += '</td>';
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
  tr += '</tr>'
  $('#table_cmd tbody').append(tr)
  var tr = $('#table_cmd tbody tr').last()
  jeedom.eqLogic.buildSelectCmd({
    id:  $('.eqLogicAttr[data-l1key=id]').value(),
    filter: {type: 'info'},
    error: function (error) {
      $('#div_alert').showAlert({message: error.message, level: 'danger'})
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result)
      tr.setValues(_cmd, '.cmdAttr')
      jeedom.cmd.changeType(tr, init(_cmd.subType))
    }
  })
}
