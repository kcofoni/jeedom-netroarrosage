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
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseigner le numéro de série du contrôleur}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="ctrl_serial_n"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Numéros de série des capteurs}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseigner les numéros de série des capteurs, séparés par un espace}}"></i></sup>
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
        <sup><i class="fas fa-question-circle tooltips" title="{{Sélectionner l'objet parent auquel sera rattaché chaque équipement à la synchronisation}}"></i></sup>
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
    <div class="form-group">
      <label class="col-md-4 control-label">{{Observation de la planification à partir de}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Indiquer le nombre de mois avant la date courante qui sera considéré en vue de déterminer la date du dernier arrosage}}"></i></sup>
      </label>
      <div class="col-md-2">
        <select class="configKey form-control" data-l1key="schedules_month_before">
          <option value="1" selected>1 {{mois avant}}</option>
          <option value="2">2 {{mois avant}}</option>
          <option value="3">3 {{mois avant}}</option>
          <option value="4">4 {{mois avant}}</option>
          <option value="5">5 {{mois avant}}</option>
          <option value="6">6 {{mois avant}}</option>        
        </select>
      </div>
      <label class="col-md-2 control-label">{{jusqu'à}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Indiquer le nombre de mois après la date courante qui sera considéré en vue de déterminer la date du prochain arrosage}}"></i></sup>
      </label>
      <div class="col-md-2">
        <select class="configKey form-control" data-l1key="schedules_month_after">
          <option value="1" selected>1 {{mois après}}</option>
          <option value="2">2 {{mois après}}</option>
          <option value="3">3 {{mois après}}</option>
          <option value="4">4 {{mois après}}</option>
          <option value="5">5 {{mois après}}</option>
          <option value="6">6 {{mois après}}</option>        
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