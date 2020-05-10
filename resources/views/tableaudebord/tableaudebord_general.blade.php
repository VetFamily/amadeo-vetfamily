<div class="tbd_general">
	<div class="tbd_graphe">
		<div id="load" class="load">
			{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
		</div>
		<canvas id="tbd_general_pie"></canvas>
	</div>
	<div class="tbd_buttons">
		<label>Objectifs à afficher</label>
		<button id="obj-atteints-buttons" type="button" style="background-color: #096A09;" onclick="generateTable(0);">Atteints</button>
		<button id="obj-atteints-condition-ko-buttons" type="button" style="background-color: #18A55D;" onclick="generateTable(1);">Atteints condition non atteinte</button>
		<button id="obj-securite-buttons" type="button" style="background-color: #1174B5;" onclick="generateTable(2);">En sécurité</button>
		<button id="obj-ligne-plus-buttons" type="button" style="background-color: #43CCCB;" onclick="generateTable(3);">En ligne +</button>
		<button id="obj-ligne-moins-buttons" type="button" style="background-color: #FD852F;" onclick="generateTable(4);">En ligne -</button>
		<button id="obj-danger-buttons" type="button" style="background-color: #DB4C3F;" onclick="generateTable(5);">En danger</button>
	</div>
	<div class="tbd_tableau">
		<label id="tbd_tableau_title"></label>
	
		<div style="display: flex; overflow-x: auto;">
			<div id="tableau" class="tableau" style='display:none; min-width: 800px;'>
				<table id='tab-objectifs' class='' cellspacing='0' width='100%'>
					<thead>
						<tr>
							<th>Espèce</th>
							<th title="Laboratoire">Labo.</th>
							<th>Nom de l'objectif</th>
							<th title="Valeur cible engagée sur l’année pour le groupe">Cible groupe</th>
							<th class="text-center">Avanc.<br><span id="echeanceAvancement"></span></th>
							<th>Ecart</th>
						</tr>
						<tr id='forFilters'>
							<th class='text-filter'></th>
							<th class='select-filter'></th>
							<th class='text-filter'></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</thead>
				</table>
			</div>
		</div>
	</div>
</div>