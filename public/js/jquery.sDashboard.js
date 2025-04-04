/*
 * jquery sDashboard (2.5)
 * Copyright 2012, Model N, Inc
 * Distributed under MIT license.
 * https://github.com/ModelN/sDashboard
 */

( function(factory) {"use strict";
		if ( typeof define === 'function' && define.amd) {
			// Register as an AMD module if available...
			define(['jquery', 'Flotr'], factory);
		} else {
			// Browser globals for the unenlightened...
			factory($, Flotr);
		}
	}(function($, Flotr) {"use strict";

		$.widget("mn.sDashboard", {
			version : "2.5",
			options : {
				dashboardData : []
			},
			_create : function() {
				this.element.addClass("sDashboard");
				this._createView();

			},
			_setOption : function(key, value) {
				this.options[key] = value;
				if (key === "dashboardData") {
					this._createView();
				}
			},

			_createView : function() {

				var docHeight = $(document).height();

				$("body").append("<div class='sDashboard-overlay'></div>");

				$(".sDashboard-overlay").height(docHeight);

				$(".sDashboard-overlay").hide();

				var _dashboardData = this.options.dashboardData;
				var i;
				for ( i = 0; i < _dashboardData.length; i++) {
					var widget = this._constructWidget(_dashboardData[i]);
					//append the widget to the dashboard
					this.element.append(widget);
					this._renderTable(_dashboardData[i]);
					this._renderChart(_dashboardData[i]);
				}

				var that = this;
				//call the jquery ui sortable on the columns
				this.element.sortable({
					handle : ".sDashboardWidgetHeader",
					start : function(event, ui) {
						ui.item.startPosition = ui.item.index();
					},
					update : function(event, ui) {
						var newPosition = ui.item.index();
						_dashboardData.splice.apply(
							_dashboardData,
							[newPosition, 0].concat(_dashboardData.splice(ui.item.startPosition, 1))
						);

						that._trigger("stateChanged", null, {
							triggerAction: 'orderChanged',
							affectedWidget: _dashboardData[newPosition]}
						);
					}
				});

				var disableSelection = this.options.hasOwnProperty("disableSelection") ? this.options.disableSelection : true;
				if (disableSelection) {
					this.element.disableSelection();
				}
				//bind events for widgets
				this._bindEvents();

				//trigger creation complete when the dashboard widgets are constructed
				this._trigger("creationComplete", null);

			},
			_getWidgetContentForId : function(id, context) {
				var widgetData = context.getDashboardData();
				for (var i = 0; i < widgetData.length; i++) {
					var widgetObject = widgetData[i];
					if (widgetObject.widgetId === id) {
						return widgetObject;
					}
				}
				return [];
			},
			_bindEvents : function() {
				var self = this;
				//click event for maximize button
				this.element.on("click", ".sDashboardWidgetHeader div.sDashboard-icon.sDashboard-zoom-plus-icon", function(e) {

					//get the widget List Item Dom
					var widgetListItem = $(e.currentTarget).parents("li:first");
					//get the widget Container
					var widget = $(e.currentTarget).parents(".sDashboardWidget:first");
					//get the widget Content
					var widgetContainer = widget.find(".sDashboardWidgetContent");

					var widgetDefinition = self._getWidgetContentForId(widgetListItem.attr("id"), self);

					//toggle the maximize icon into minimize icon
					$(e.currentTarget).toggleClass("sDashboard-zoom-minus-icon");
					//change the tooltip on the maximize/minimize icon buttons
					if ($(e.currentTarget).attr("title") === "Agrandir") {
						$(".sDashboard-overlay").show();
						$(e.currentTarget).attr("title", "Réduire");
						self._trigger("widgetMaximized", null, {
							"widgetDefinition" : widgetDefinition
						});
						//remove the delete icon
						$(e.currentTarget).prev().remove();
					} else {
						$(".sDashboard-overlay").hide();
						$(e.currentTarget).attr("title", "Agrandir");
						self._trigger("widgetMinimized", null, {
							"widgetDefinition" : widgetDefinition
						});
						//add the delete icon
						if (widgetDefinition.hasOwnProperty("enableDelete") && widgetDefinition.enableDelete) {
							$( '<div title="Fermer" class="sDashboard-icon sDashboard-delete-icon"></div>' ).insertBefore( $(e.currentTarget) );
						}
					}

					//toggle the class for widget and inner container
					widget.toggleClass("sDashboardWidgetContainerMaximized");
					widgetContainer.toggleClass("sDashboardWidgetContentMaximized ");

					if (widgetDefinition.widgetType === "chart") {
						var chartArea = widgetContainer.find(" div.sDashboardChart");
						Flotr.draw(chartArea[0], widgetDefinition.widgetContent.data, widgetDefinition.widgetContent.options);
						if (!widgetDefinition.getDataBySelection) {
							//when redrawing the widget, the click event listner is getting destroyed, we need to re-register it here again
							//need to find out if its a bug on flotr2 library.
							self._bindChartEvents(chartArea[0], widgetListItem.attr("id"), widgetDefinition, self);
						}
					}
				});

				//refresh widget click event handler
				this.element.on("click", ".sDashboardWidgetHeader div.sDashboard-icon.sDashboard-refresh-icon", function(e) {
					var widget = $(e.currentTarget).parents("li:first");
					var widgetId = widget.attr("id");
					var widgetDefinition = self._getWidgetContentForId(widgetId, self);
					var refreshedData = widgetDefinition.refreshCallBack.apply(self, [widgetId]);
					widgetDefinition.widgetContent = refreshedData;
					if (widgetDefinition.widgetType === 'chart') {
						self._renderChart(widgetDefinition);
					} else if (widgetDefinition.widgetType === 'table') {
						self._refreshTable(widgetDefinition, widget);
					} else {
						self._refreshRegularWidget(widgetDefinition, widget);
					}

				});

				//delete widget by clicking the 'x' icon on the widget
	            this.element.on("click", ".sDashboardWidgetHeader div.sDashboard-icon.sDashboard-delete-icon ", function(e) {
					var widget = $(e.currentTarget).parents("li:first");
					var message = '<p class="avertissement">La suppression du widget "' + widget.children().eq(0).children().eq(0).text() + '" est définitive.</p><p class="question">Êtes-vous sûr de vouloir continuer ?</p>';
		    		confirmBox(
	            		"Suppression du widget", 
	            		message, 
	            		"Oui", 
	            		"Non", 
	            		function() 
	            		{
							//show hide effect
							widget.hide("fold", {}, 300, function() {
								self._removeWidgetFromWidgetDefinitions(this.id);
								$(this).remove();
								$(".sDashboard-overlay").hide();
							});
	            		},
	            		function() { }
	            	);
				});

				//table row click
				this.element.on("click", ".sDashboardWidgetContent table.sDashboardTableView tbody tr", function(e) {
					var selectedRow = $(e.currentTarget);

					if (selectedRow.length > 0) {
						var selectedDataTable = selectedRow.parents('table:first').dataTable();

						var selectedWidget = selectedRow.parents("li:first");
						var selectedRowData = selectedDataTable.fnGetData(selectedRow[0]);
						var selectedWidgetId = selectedWidget.attr("id");
						var evtData = {
							selectedRowData : selectedRowData,
							selectedWidgetId : selectedWidgetId
						};

						//trigger dashboardTableViewRowClick changed event
						self._trigger("rowclicked", null, evtData);
					}
				});
			},

			_constructWidget : function(widgetDefinition) {
				//create an outer list item
				var widget = $("<li/>").attr("id", widgetDefinition.widgetId);
				//create a widget container
				var widgetContainer = $("<div/>").addClass("sDashboardWidget");

				//create a widget header
				var widgetHeader = $("<div/>").addClass("sDashboardWidgetHeader sDashboard-clearfix");
				var maximizeButton = $('<div title="Agrandir" class="sDashboard-icon sDashboard-zoom-plus-icon "></span>');

				//add delete button
				if (widgetDefinition.hasOwnProperty("enableDelete") && widgetDefinition.enableDelete) {
					var deleteButton = $('<div title="Fermer" class="sDashboard-icon sDashboard-delete-icon"></div>');
					widgetHeader.append(deleteButton);
				}
				//add Maximizebutton
				widgetHeader.append(maximizeButton);

				if (widgetDefinition.hasOwnProperty("enableRefresh") && widgetDefinition.enableRefresh) {
					var refreshButton = $('<div title="Rafraîchir" class="sDashboard-icon sDashboard-refresh-icon "></div>');
					//add refresh button
					widgetHeader.append(refreshButton);
				}

				//add widget title
				widgetHeader.append(widgetDefinition.widgetTitle);

				//create a widget content
				var widgetContent = $("<div/>").addClass("sDashboardWidgetContent");

				// Ajout d'une zone d'informations facultative
				if (widgetDefinition.hasOwnProperty("enableInformations") && widgetDefinition.enableInformations)
				{
					var informationsZone = "<div class='sDashboardInformationsZone'>" + widgetDefinition.widgetInformations + "</div>";
					widgetContent.append(informationsZone);
				}

				if (widgetDefinition.widgetType === 'table') {
					var dataTable = $('<table cellpadding="0" cellspacing="0" border="0" class="display sDashboardTableView table table-bordered"></table>');
					widgetContent.append(dataTable);
				} else if (widgetDefinition.widgetType === 'chart') {
					var chart = $('<div/>').addClass("sDashboardChart");
					if (widgetDefinition.getDataBySelection) {
						chart.addClass("sDashboardChartSelectable");
					} else {
						chart.addClass("sDashboardChartClickable");
					}
					widgetContent.append(chart);
				} else {
					widgetContent.append(widgetDefinition.widgetContent);
				}

				//add widgetHeader to widgetContainer
				widgetContainer.append(widgetHeader);
				//add widgetContent to widgetContainer
				widgetContainer.append(widgetContent);

				//append the widgetContainer to the widget
				widget.append(widgetContainer);

				//return widget
				return widget;
			},
			_refreshRegularWidget : function(widgetDefinition, widget) {
				var isMaximized = widget.find(".sDashboardWidgetContent").hasClass('sDashboardWidgetContentMaximized');
				//first remove the content
				widget.find('.sDashboardWidgetContent').empty().remove();
				//then create the content again
				var widgetContent = $("<div/>").addClass("sDashboardWidgetContent");
				//if its maximized add the maximized class
				if (isMaximized) {
					widgetContent.addClass('sDashboardWidgetContentMaximized');
				}
				widgetContent.append(widgetDefinition.widgetContent);
				//then append this to the widget again;
				widget.find(".sDashboardWidget").append(widgetContent);
			},
			_refreshTable : function(widgetDefinition, widget) {
				var selectedDataTable = widget.find('table:first').dataTable();
				selectedDataTable.fnClearTable();
				selectedDataTable.fnAddData(widgetDefinition.widgetContent["aaData"]);

			},
			_renderTable : function(widgetDefinition){
				var id = "li#" + widgetDefinition.widgetId;
				var table
				if(widgetDefinition.widgetType === 'table'){
					table = this.element.find(id + " table.sDashboardTableView");

					var tableDef = {};
					$.extend(tableDef,widgetDefinition.widgetContent);

					if (widgetDefinition.setJqueryStyle) {
						tableDef["bJQueryUI"] = true;
					}
					table.dataTable(tableDef);
				}
			},
			_renderChart : function(widgetDefinition) {
				var id = "li#" + widgetDefinition.widgetId;
				var chartArea;
				var data;
				var options;

				if (widgetDefinition.widgetType === 'chart') {
					chartArea = this.element.find(id + " div.sDashboardChart");
					data = widgetDefinition.widgetContent.data;
					options = widgetDefinition.widgetContent.options;
					Flotr.draw(chartArea[0], data, options);
					if (widgetDefinition.getDataBySelection) {
						this._bindSelectEvent(chartArea[0], widgetDefinition.widgetId, widgetDefinition, this);
					} else {
						this._bindChartEvents(chartArea[0], widgetDefinition.widgetId, widgetDefinition, this);
					}
				}

			},
			_bindSelectEvent : function(chartArea, widgetId, widgetDefinition, context) {
				Flotr.EventAdapter.observe(chartArea, "flotr:select", function(area) {
					var evtObj = {
						selectedWidgetId : widgetId,
						chartData : area
					};
					context._trigger("plotselected", null, evtObj);
				});
			},
			_bindChartEvents : function(chartArea, widgetId, widgetDefinition, context) {

				Flotr.EventAdapter.observe(chartArea, 'flotr:click', function(d) {
					//only if a series is clicked dispatch a click event
					if (d.index !== undefined && d.seriesIndex !== undefined) {
						var evtObj = {};
						evtObj.selectedWidgetId = widgetId;
						evtObj.flotr2GeneratedData = d;
						var widgetData = widgetDefinition.widgetContent.data;
						var seriesData = widgetData[d.seriesIndex];
						var selectedData;

						if ($.isArray(seriesData)) {
							selectedData = seriesData[d.index];
						} else {
							selectedData = seriesData;
						}

						evtObj.customData = {
							index : d.index,
							selectedIndex : d.seriesIndex,
							seriesData : seriesData,
							selectedData : selectedData
						};
						context._trigger("plotclicked", null, evtObj);
					}
				});

			},
			_removeWidgetFromWidgetDefinitions : function(widgetId) {
				var widgetDefs = this.options.dashboardData;
				for (var i in widgetDefs) {
					var currentWidget = widgetDefs[i];
					if (currentWidget.widgetId === widgetId) {
						widgetDefs.splice(i, 1);
						this._trigger("stateChanged", null,  {
								triggerAction: 'widgetRemoved',
								affectedWidget: currentWidget
							}
						);
						break;
					}
				}
			},

			_ifWidgetAlreadyExists : function(widgetId) {
				if (!widgetId) {
					throw "Expected widgetId to be defined";
				}
				var idSelector = "#" + widgetId;
				//get the dom element
				var widget = this.element.find("li" + idSelector);
				if (widget.length > 0) {
					return true;
				}
				return false;
			},

			/*public methods*/
			//add a widget to the dashbaord
			addWidget : function(widgetDefinition) {
				if (!widgetDefinition.widgetId) {
					throw "Expected widgetId to be defined";
				}

				if (this._ifWidgetAlreadyExists(widgetDefinition.widgetId)) {
					this.element.find("li#" + widgetDefinition.widgetId).effect("shake", {
						times : 3
					}, 800);
				} else {
					this.options.dashboardData.unshift(widgetDefinition);
					var widget = this._constructWidget(widgetDefinition);
					this.element.prepend(widget);
					this._renderChart(widgetDefinition);
					this._renderTable(widgetDefinition);
					this._trigger("stateChanged", null,  {
							triggerAction: 'widgetAdded',
							affectedWidget: widgetDefinition
						}
					);
				}
			},
			//remove a widget from the dashboard
			removeWidget : function(widgetId) {
				if (!widgetId) {
					throw "Expected widgetId to be defined";
				}
				var idSelector = "#" + widgetId;
				//get the dom element
				var widget = this.element.find("li" + idSelector);
				if (widget.length > 0) {
					//delete the dom element
					this.element.find("li" + idSelector).remove();
					//remove the dom element from the widgetDefinition
					this._removeWidgetFromWidgetDefinitions(widgetId);
				}
			},

			//get the wigetDefinitions
			getDashboardData : function() {
				return this.options.dashboardData;
			},
			destroy : function() {
				//remove the overlay when the dashbaord is destroyed
				$(".sDashboard-overlay").remove();
				// call the base destroy function
				$.Widget.prototype.destroy.call(this);
			}
		});

	}));

