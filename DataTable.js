  $('#tcomissaoVendedor').DataTable({
                        "language": {
                            "lengthMenu": "Mostrando _MENU_ por pág.",
                            "zeroRecords": "Nada encontrado - desculpe",
                            "info": "pág _PAGE_ de _PAGES_ com _TOTAL_ resultados",
                            "infoEmpty": "Nenhum resultado disponivel",
                            "infoFiltered": "(filtrando de _MAX_ total resultados)",
                            "search": 'Procurar',
                            "paginate": {
                                "previous": "Pág. ant.",
                                "next": "Próx. pág."
                            }
                        },
                        "footerCallback": function (row, data, start, end, display) {
                            var api = this.api(), data;

                            // Remove the formatting to get integer data for summation
                            var intVal = function (i) {
                                i = parseFloat(i.toString().replace(',', '.'));
                                return typeof i === 'string' ?
                                        i.replace(/[\$,]/g, '') * 1 :
                                        typeof i === 'number' ?
                                        i : 0;
                            };

                            // Total over all pages
                            total = api
                                    .column(4)
                                    .data()
                                    .reduce(function (a, b) {
                                        var soma = intVal(a) + intVal(b);
                                        return intVal(a) + intVal(b);
                                    });
                             
                            // Total over this page
                            pageTotal = api
                                    .column(4, {page: 'current'})
                                    .data()
                                    .reduce(function (a, b) {
                                        return intVal(a) + intVal(b);
                                    }, 0);

                            // Update footer
                            $(api.column(4).footer()).html(
                                    'R$ ' + parseFloat(pageTotal).toFixed(2).toString().replace('.', ',') + ' ( R$ ' + parseFloat(total).toFixed(2).toString().replace('.', ',') + ' total)'
                                    );

                            /** Total comissão*/
                            totalComissao = api
                                    .column(5)
                                    .data()
                                    .reduce(function (a, b) {
                                        var soma = intVal(a) + intVal(b);
                                        return intVal(a) + intVal(b);
                                    });
                             
                            // Total over this page
                            pageTotalComissao = api
                                    .column(5, {page: 'current'})
                                    .data()
                                    .reduce(function (a, b) {
                                        return intVal(a) + intVal(b);
                                    }, 0);

                            // Update footer
                            $(api.column(5).footer()).html(
                                    'R$ ' + parseFloat(pageTotalComissao).toFixed(2).toString().replace('.', ',') + ' ( R$ ' + parseFloat(totalComissao).toFixed(2).toString().replace('.', ',') + ' total)'
                                    );
                        }
                    });
