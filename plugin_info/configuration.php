<?php
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>

<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Numéro de série du controleur}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le numéro de série du contrôleur}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="ctrl_serial_n"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Numéros de série des capteurs}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez les numéros de série des capteurs, séparés par un espace}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="sensor_serial_n"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Facteur de ralentissement}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Fournir les tranches horaires et le facteur de ralentissement souhaité sur chacune d'elle}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="slowdown_factor"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Objet parent par défaut}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Sélectionnez l'objet parent auquel sera rattaché chaque équipement à la synchronisation}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select id="sel_object" class="configKey form-control" data-l1key="default_parent_object">
          <option value="">{{Aucune}}</option>
          <?php
          foreach (jeeObject::all() as $object) {
            echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
          }
          ?>
        </select>
      </div>
    </div>
  </fieldset>
</form>

<script>
    $("input[data-l1key='functionality::cron5::enable']").on('change',function(){
        if ($(this).is(':checked')) $("input[data-l1key='functionality::cron::enable']").prop("checked", false)
    });

    $("input[data-l1key='functionality::cron::enable']").on('change',function(){
        if ($(this).is(':checked')) $("input[data-l1key='functionality::cron5::enable']").prop("checked", false)
    });
</script>