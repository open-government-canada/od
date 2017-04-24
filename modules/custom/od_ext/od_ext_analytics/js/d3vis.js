(function ($) {
  'use strict';
  Drupal.behaviors.d3vis = {
    attach: function (context, settings) {
      // This just ensures this JS is called once to avoid Drupal ajax recalls
      $(context).find('.block-region-top').once('d3vis').each(function () {

        var lang = document.documentElement.lang;

        var barDefaults = {
            margin: {
              top: 0,
              right: 0,
              bottom: 30,
              left: 50,
            },
            aspectRatio: 16 / 9,
            x: {
              ticks: 5,
            },
            y: {
              ticks: 10,
            },
          };

        var pieDefaults = {
            aspectRatio: 1,
            i18n: {
              pieLegend: (lang === 'fr' ? 'Couleur du graphique' : 'Chart colour'),
              piePercent: (lang === 'fr' ? 'Pourcentage des ' : 'Percentage of '),
              pieTotal: (lang === 'fr' ? 'Nombre total de ' : 'Total Number of '),
            },
          };

        var outerWidth = 600;

        var d3Locale = (lang === 'fr' ? d3.formatLocale({
            decimal: ',',
            thousands: ' ',
            grouping: [3],
            currency: ['$', ''],
          }) : d3.formatLocale({
            decimal: '.',
            thousands: ',',
            grouping: [3],
            currency: ['$', ''],
          }));

        // Timechart.
        var getTimeChart = function (settings) {
          var callback = function (error, data) {
              var outerHeight = Math.ceil(outerWidth / settings.aspectRatio);
              var chart = d3.select(settings.selector)
                  .attr('viewBox', '0 0 ' + outerWidth + ' ' + outerHeight)
                  .attr('preserveAspectRatio', 'xMidYMid meet');
              var chartInner = chart.append('g')
                  .attr("transform", "translate(" + settings.margin.left + "," + settings.margin.top + ")");
              var innerHeight = outerHeight - settings.margin.top - settings.margin.bottom;
              var innerWidth = outerWidth - settings.margin.left - settings.margin.right;
              var x = d3.scaleTime().range([0, innerWidth]);
              var y = d3.scaleLinear().range([innerHeight, 0]);
              var xAxis = d3.axisBottom(x).ticks(settings.x.ticks);
              var yAxis = d3.axisLeft(y).ticks(settings.y.ticks);
              var line = d3.line()
                  .x(function (d) {
                    return x(settings.x.getValue(d));
                  }).y(function (d) {
                    return y(settings.y.getValue(d));
                  });

              var dataLayer;
              var table;

              chart.append('style')
                .text(".line{fill:none;stroke-linejoin:round;stroke-linecap:round;stroke-width:1.5} .line1{stroke:steelblue}");

              x.domain(d3.extent(data, settings.x.getValue));

              y.domain([
                0,
                d3.max(data, settings.y.getValue),
              ]);

              chartInner.append('g')
                .attr('class', 'x axis')
                .attr("transform", "translate(0," + innerHeight + ")")
                .call(xAxis);

              chartInner.append('g')
                .attr('class', 'y axis')
                .call(yAxis);

              dataLayer = chartInner.selectAll('.data')
                .data([data])
                .enter().append('g')
                  .attr('class', 'data');

              dataLayer.append('path')
                .attr('class', 'line line1')
                .attr('d', line);

              table = chart.select(function(){return this.parentNode;})
                .append('table')
                  .attr('class', 'table');

              table.append('thead').append('tr')
                .selectAll('th')
                .data(data).enter()
                .append('th')
                .text(function (d) {return d.nothing;});

              table.append('tbody').append('tr')
                .selectAll('td')
                .data(data).enter()
                .append('td')
                .text(settings.y.getValue);
            };

          d3.json(settings.url, callback);
        };

        // Piechart.
        var getPieChart = function (settings) {
            var callback = function (error, data) {
              var outerHeight = Math.ceil(outerWidth / settings.aspectRatio);
              var chart = d3.select(settings.selector)
                  .attr('viewBox', '0 0 ' + outerWidth + ' ' + outerHeight)
                  .attr('preserveAspectRatio', 'xMidYMid meet');
              var  chartInner = chart.append('g')
                  .attr('transform', "translate(" + outerWidth / 2 + "," + outerHeight / 2 + ")");
              var radius = Math.min(outerWidth, outerHeight) / 2;

              var sum = d3.sum(data, settings.y.getValue);
              var percentageFormat = d3Locale.format('.0%');
              var floatPercentageFormat = d3Locale.format('.2%');
              var numberFormat = d3Locale.format(',.0f');
              var arc = d3.arc()
                  .outerRadius(radius)
                  .innerRadius(radius / 3);
              var  labelArc = d3.arc()
                  .outerRadius(radius - 20)
                  .innerRadius(radius - 20);
              var pie = d3.pie()
                   .value(settings.y.getValue);
              var dataLayer;
              var textLayer;
              var textGroups;
              var table;
              var tableHeaders;
              var row;
              var tableFoot;

              chart.append('style')
                .text(".part-text{text-anchor:middle; font-size:80%;}.part-text-bg{fill:#fff;stroke:#000}.part{stroke:#fff; stroke-width:1}")
              dataLayer = chartInner
                .append('g')
                  .attr('class', 'data');
              dataLayer.selectAll('path')
                .data(pie(data)).enter()
                .append('path')
                .attr('d', arc)
                .attr('class', function (d, i) {return 'part part' + (i + 1);});

              textLayer = chartInner
                .append('g')
                  .attr('class', 'text');
              textLayer.selectAll('g')
                .data(pie(data)).enter()
                .filter(function (d, i) {
                  return (2 * Math.PI * radius * (d.users / sum)) > 15;
                })
                .append('g');
              textLayer.selectAll('g').append('text')
                  .attr('transform', function (d) { return "translate(" + labelArc.centroid(d) + ")"; })
                  .attr('class', 'part-text')
                  .text(function (d) { return percentageFormat(settings.y.getValue(d) / sum); });

              textLayer.selectAll('g')
                .call(function (selection) {
                  selection.each(function (d) {d.bbox = this.getBBox();});
                })
                .insert('rect', 'text')
                  .attr('class', 'part-text-bg')
                  .attr('x', function (d) {return d.bbox.x - 2;})
                  .attr('y', function (d) {return d.bbox.y - 2;})
                  .attr('height', function (d) {return d.bbox.height + 4;})
                  .attr('width', function (d) {return d.bbox.width + 4;});

              table = chart.select(function () {return this.parentNode;})
                .append('table')
                  .attr('class', 'table');
              tableHeaders = table.append('thead').append('tr');
              tableHeaders.append('th').text(settings.i18n.pieLegend).attr('style', 'width:50px;');
              tableHeaders.append('th').text(settings.x.header);
              tableHeaders.append('th').text(settings.y.header);
              tableHeaders.append('th').text(settings.i18n.piePercent + settings.y.header);
              row = table.append('tbody')
                .selectAll('tr')
                .data(data).enter()
                .append('tr');
              row.append('td').attr('class', function (d, i) {return 'part' + (i + 1);});

              row.append('td').text(settings.x.getValue);
              row.append('td').text(function (d) {return numberFormat(settings.y.getValue(d));});

              row.append('td').text(function (d) {return floatPercentageFormat(settings.y.getValue(d) / sum);});

              tableFoot = table.append('tfoot').append('tr');
              tableFoot.append('th').attr('colspan', '2').text(settings.i18n.pieTotal + settings.y.header);

              tableFoot.append('td').attr('colspan', '2').append('strong').text(function (d) {return numberFormat(sum);});
            };

            d3.json(settings.url, callback);
          };

        getTimeChart($.extend(true, {}, barDefaults, {
          url: '/api/visits',
          selector: '.visits',
          x: {
            getValue: function (d) {
              return new Date(d.nothing);
            },
          },
          y: {
            getValue: function (d) {
              return d.users;
            },
          },
        }));

        getTimeChart($.extend(true, {}, barDefaults, {
          url: '/api/downloads',
          selector: '.downloads',
          x: {
            getValue: function (d) {
              return new Date(d.nothing);
            },
          },
          y: {
            getValue: function (d) {
              return d.users;
            },
          },
        }));

        getPieChart($.extend(true, {}, pieDefaults, {
          url: '/api/country',
          selector: '.countryVisits',
          x: {
            header: 'Country',
            getValue: function (d) {
              return d.country;
            },
          },
          y: {
            header: 'Visits',
            getValue: function (d) {
              return d.users;
            },
          },
        }));

        getPieChart($.extend(true, {}, pieDefaults, {
          url: '/api/country/foreign',
          selector: '.foreignCountryVisits',
          x: {
            header: 'Country',
            getValue: function (d) {
              return d.country;
            },
          },
          y: {
            header: 'Visits',
            getValue: function (d) {
              return d.users;
            },
          },
        }));

        getPieChart($.extend(true, {}, pieDefaults, {
          url: '/api/country/canada',
          selector: '.provinceVisits',
          x: {
            header: 'Region',
            getValue: function (d) {
              return d.region;
            },
          },
          y: {
            header: 'Visits',
            getValue: function (d) {
              return d.users;
            },
          },
        }));

      });
    },
  };
}(jQuery));
