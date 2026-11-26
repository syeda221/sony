@extends('admin_panel.layout.app')
@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-header">
            <h5>➕ New Stock Transfer</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('stock_transfers.store') }}" method="POST">
                @csrf
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="mb-3">
                    <label>From Warehouse</label>
                    <select name="from_warehouse_id" id="from_warehouse_id" class="form-control" required>
                        <option value="">Select Warehouse</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <div class="row">
                        <div class="col-lg-6">
                            <label class="form-label fw-bold">To Warehouse</label>
                            <select name="to_warehouse_id" id="to_warehouse_id" class="form-control" required>
                                <option value="">Select Destination Warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label fw-bold" for="toShop">Or Transfer to Shop</label>
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" name="to_shop" value="1" id="toShop">
                                <label class="form-check-label ms-2" for="toShop">Transfer Directly to Shop</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold mb-0">Products to Transfer</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_add_row">
                        <i class="fas fa-plus"></i> Add Another Product
                    </button>
                </div>

                <table class="table table-bordered text-center align-middle" id="product_table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">Product</th>
                            <th style="width: 20%;">Available Stock</th>
                            <th style="width: 20%;">Transfer Qty</th>
                            <th style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="product_body">
                        <tr class="product_row">
                            <td>
                                <select name="product_id[]" class="form-control product-select" required>
                                    <option value="">Select Warehouse First</option>
                                </select>
                                <div class="invalid-feedback product-error"
                                    style="display:none; font-size: 0.85rem; text-align: left;">
                                    Product already selected
                                </div>
                            </td>
                            <td>
                                <input type="number" name="available_stock[]" class="form-control stock" readonly placeholder="0">
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="form-control quantity" required min="1" placeholder="Qty">
                                <div class="invalid-feedback stock-error"
                                    style="display:none; font-size: 0.85rem; text-align: left;">
                                    Insufficient Stock
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-row">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mb-3">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control"></textarea>
                </div>

                <button type="submit" class="btn btn-success">Transfer Stock</button>
            </form>
        </div>
    </div>
@endsection
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>

<script>
    $(document).ready(function() {
        let availableProducts = [];

        // Toggle Shop vs Warehouse
        $('#toShop').on('change', function() {
            var toWhSelect = $('#to_warehouse_id');
            if ($(this).is(':checked')) {
                toWhSelect.val('').prop('disabled', true).prop('required', false);
            } else {
                toWhSelect.prop('disabled', false).prop('required', true);
            }
            validateForm();
        });

        // 1. Fetch Products when Source Warehouse Changes
        $('#from_warehouse_id').change(function() {
            var warehouseId = $(this).val();

            if (!warehouseId) {
                availableProducts = [];
                updateAllProductDropdowns();
                return;
            }

            // Prevent selecting same warehouse as destination
            $('#to_warehouse_id option').each(function() {
                if ($(this).val() == warehouseId && $(this).val() !== '') {
                    $(this).prop('disabled', true);
                    if ($('#to_warehouse_id').val() == warehouseId) {
                        $('#to_warehouse_id').val('');
                    }
                } else {
                    $(this).prop('disabled', false);
                }
            });

            $.ajax({
                url: "{{ route('get.products.by.warehouse') }}",
                data: {
                    warehouse_id: warehouseId
                },
                success: function(products) {
                    availableProducts = products;
                    updateAllProductDropdowns();
                },
                error: function() {
                    alert('Error fetching products for this warehouse');
                }
            });
        });

        $('#to_warehouse_id').change(function() {
            validateForm();
        });

        // Update all dropdowns with available products
        function updateAllProductDropdowns() {
            $('.product-select').each(function() {
                var currentVal = $(this).val();
                var options = '<option value="">Select Product</option>';

                if (availableProducts.length === 0) {
                    options = '<option value="">No available stock in this warehouse</option>';
                } else {
                    availableProducts.forEach(function(p) {
                        var selected = (p.id == currentVal) ? 'selected' : '';
                        options += `<option value="${p.id}" ${selected}>${p.item_name} (Stock: ${p.available_stock})</option>`;
                    });
                }
                $(this).html(options);

                // If previous selection is no longer valid, clear stock/qty
                var matching = availableProducts.find(p => p.id == currentVal);
                if (!matching) {
                    $(this).closest('tr').find('.stock').val('');
                    $(this).closest('tr').find('.quantity').val('').removeAttr('max');
                } else {
                    $(this).closest('tr').find('.stock').val(matching.available_stock);
                    $(this).closest('tr').find('.quantity').attr('max', matching.available_stock);
                }
            });
            validateForm();
        }

        // 2. Fetch Stock Quantity when Product Selected
        $(document).on('change', '.product-select', function() {
            var currentRow = $(this).closest('tr');
            var selectedProduct = $(this).val();
            var fromWarehouse = $('#from_warehouse_id').val();

            if (selectedProduct && fromWarehouse) {
                var matching = availableProducts.find(p => p.id == selectedProduct);
                if (matching) {
                    currentRow.find('.stock').val(matching.available_stock);
                    currentRow.find('.quantity').attr('max', matching.available_stock);
                    validateForm();
                }

                $.ajax({
                    url: "{{ route('warehouse.stock.quantity') }}",
                    method: 'GET',
                    data: {
                        warehouse_id: fromWarehouse,
                        product_id: selectedProduct
                    },
                    success: function(response) {
                        var avail = response.quantity !== undefined ? response.quantity : (response.total_pieces || 0);
                        currentRow.find('.stock').val(avail);
                        currentRow.find('.quantity').attr('max', avail);

                        if (avail <= 0) {
                            currentRow.find('.stock').addClass('text-danger fw-bold');
                        } else {
                            currentRow.find('.stock').removeClass('text-danger fw-bold');
                        }

                        validateForm();
                    }
                });
            } else {
                currentRow.find('.stock').val('');
                currentRow.find('.quantity').removeAttr('max');
                validateForm();
            }

            // Auto-add new row if it's the last one and product selected
            if ($('#product_body tr:last').is(currentRow) && selectedProduct) {
                addNewRow();
            }
        });

        // 3. Validate Quantity & Duplicates
        function validateForm() {
            let isValid = true;
            let productCounts = {};
            let hasAtLeastOne = false;

            var fromWh = $('#from_warehouse_id').val();
            var toWh = $('#to_warehouse_id').val();
            var toShop = $('#toShop').is(':checked');

            if (!fromWh) {
                isValid = false;
            }

            if (!toShop && !toWh) {
                isValid = false;
            }

            if (!toShop && fromWh && toWh && fromWh == toWh) {
                isValid = false;
            }

            // Count occurrences of each product
            $('.product-select').each(function() {
                let val = $(this).val();
                if (val) {
                    productCounts[val] = (productCounts[val] || 0) + 1;
                }
            });

            // Validate Products (Duplicates)
            $('.product-select').each(function() {
                let val = $(this).val();
                let errorDiv = $(this).next('.product-error');

                if (val && productCounts[val] > 1) {
                    $(this).addClass('is-invalid');
                    errorDiv.show();
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                    errorDiv.hide();
                }
            });

            $('.quantity').each(function() {
                var entered = parseFloat($(this).val()) || 0;
                var max = parseFloat($(this).attr('max')) || 0;
                var productSelected = $(this).closest('tr').find('.product-select').val();
                var errorDiv = $(this).next('.stock-error');

                if (productSelected) {
                    if (entered > 0) {
                        hasAtLeastOne = true;
                    }
                    if (entered > max) {
                        $(this).addClass('is-invalid');
                        errorDiv.text('Insufficient Stock! Available: ' + max).show();
                        isValid = false;
                    } else if (entered <= 0 && $(this).prop('required')) {
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                        errorDiv.hide();
                    }
                }
            });

            if (!hasAtLeastOne) {
                isValid = false;
            }

            // Disable/Enable submit button
            $('button[type="submit"]').prop('disabled', !isValid);
        }

        $(document).on('input', '.quantity', function() {
            validateForm();
        });

        $(document).on('change', '.product-select', function() {
            validateForm();
        });

        // 4. Remove Row
        $(document).on('click', '.remove-row', function() {
            if ($('#product_body tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                var row = $(this).closest('tr');
                row.find('select').val('');
                row.find('input').val('');
                row.find('.stock').val('');
                row.find('.quantity').removeClass('is-invalid');
            }
            validateForm();
        });

        // 5. Add New Row Function
        function addNewRow() {
            var options = '<option value="">Select Product</option>';
            if (availableProducts.length > 0) {
                availableProducts.forEach(function(p) {
                    options += `<option value="${p.id}">${p.item_name} (Stock: ${p.available_stock})</option>`;
                });
            } else {
                options = '<option value="">Select Warehouse First</option>';
            }

            var row = `
                <tr class="product_row">
                    <td>
                        <select name="product_id[]" class="form-control product-select" required>
                            ${options}
                        </select>
                        <div class="invalid-feedback product-error" style="display:none; font-size: 0.85rem; text-align: left;">
                            Product already selected
                        </div>
                    </td>
                    <td>
                        <input type="number" name="available_stock[]" class="form-control stock" readonly placeholder="0">
                    </td>
                    <td>
                        <input type="number" name="quantity[]" class="form-control quantity" required min="1" placeholder="Qty">
                        <div class="invalid-feedback stock-error" style="display:none; font-size: 0.85rem; text-align: left;">
                            Insufficient Stock
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-row">Remove</button>
                    </td>
                </tr>
            `;
            $('#product_body').append(row);
        }

        $('#btn_add_row').on('click', function() {
            addNewRow();
        });
    });
</script>
