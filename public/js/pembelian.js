$(function () {
    $("#reload-price-ico").hide();
    $("#item_packge_id").select2("val", "0");
    // ** ↓ Tambah Barang ke daftar ↓ **
    $("#item_packge_id").change(function () {
        $("#subtotal_amount").val("");
        $("#subtotal_amount_view").val("");
        $("#discount_percentage").val("");
        $("#discount_amount").val("");
        $("#quantity").val("");
        $("#discount_amount_view").val("");
        $("#subtotal_amount_after_discount").val("");
        $("#subtotal_amount_after_discount_view").val("");
        if (this.value != "") {
            loadingWidget();
            disableButton();
            $.ajax({
                url: route('select-item-detail')+ '/' + this.value,
                type: "GET",
                dataType: "json",
                success: function (data) {
                    $('#item_unit_cost').val(data.cost);
                    $('#item_unit_cost_view').val(toRp(data.cost));
                    $('#item_unit_cost_old').val(data.cost);
                    $("#item_unit_cost_ori").val(data.cost);
                    if(data.initem){
                        if(data.costchanged){
                            $('#alert-cost-changed').show();
                            $('#item_unit_cost').val(data.data.item_unit_cost);
                            $('#item-name-alert').html("'"+data.data.item_name+"'");
                            $('#item-cost-old-alert').html(number_format(data.cost));
                            $('#item_unit_cost_view').val(toRp(data.data.item_unit_cost));
                        }
                        $('#alert-exists').show();
                        $('#discount_percentage').val(data.data.discount_percentage);
                    }else{
                        $('#alert-cost-changed').hide();
                        $('#alert-exists').hide();
                        // $('#discount_percentage').val(data.item_unit_discount);
                    }
                    setTimeout(() => {
                        enableButton();
                        loadingWidget(0);
                    }, 200);
                },
                error: function(data) {
                    $("#item_packge_id").select2("val", "0");
                    setTimeout(() => {
                        disableButton();
                        loadingWidget(0);
                    }, 200);
                    alert(
                        'Terjadi Kesalahan, Pastikan semua data sudah terisai dan periksa internet anda lalu coba lagi.')
                }
            });
        } else {
            $("#item_unit_cost").val("");
            $("#item_unit_cost_ori").val("");
            $("#item_unit_cost_view").val("");
            $("#subtotal_amount").val("");
            $("#subtotal_amount_view").val("");
            $("#discount_percentage").val("");
            $("#discount_amount").val("");
            $("#quantity").val("");
            $("#discount_amount_view").val("");
            $("#subtotal_amount_after_discount").val("");
            $("#subtotal_amount_after_discount_view").val("");
        }
    });
    // $('#change-price-view').hide();
    $("#quantity").on("keyup keydown change",function() {
        calcPurchase();
        enableButton();
    });
    $("#reload-price-ico").click(function (e) {
        e.preventDefault();
        resetCostChanged();
    });
    $("#item_unit_cost_view").change(function () {
        let item_packge_id = $("#item_packge_id").val();
        let cost_new = $("#item_unit_cost_view").val();
        let cost = $("#item_unit_cost").val();
        let cost_old = $("#item_unit_cost_ori").val();
        let cost_now = $(this).val();
        $('#item_unit_cost').val(this.value);
        $('#item_unit_cost_view').val(toRp(this.value));
        if(checkIfCostIsChaged()){
            $.ajax({
                url: route("select-item-price") + "/" + item_packge_id,
                type: "GET",
                dataType: "html",
                success: function (price) {
                    if (price != "") {
                        if (cost != cost_new) {
                            $.ajax({
                                url:route("get-margin-category") +"/" +item_packge_id,
                                type: "GET",
                                dataType: "html",
                                success: function (margin) {
                                    if (margin != "") {
                                        $("#margin_percentage").val(margin);
                                        $("#margin_percentage_old").val(margin);
                                        var price_new =parseInt(cost_new) + (parseInt(cost_new) * margin) / 100;
                                        $("#item_price_new_view").val(toRp(price_new));
                                        $("#item_price_new").val(price_new);
                                    }
                                },
                            });
                            if(parseInt(cost_now)!=parseInt($("#item_unit_cost_ori").val())){
                                            $("#modal").modal("show");
                                            $("#item_price_old_view").val(toRp(price));
                                            $("#item_cost_old_view").val(toRp(cost_old));
                                            $("#item_cost_new_view").val(toRp(cost_new));
                                            $("#item_price_old").val(price);
                                            $("#item_cost_old").val(cost_old);
                                            $("#item_cost_new").val(cost_new);
                            }
                        }
                    }
                },
            });
        }
        calcPurchase();
    });
    $('#purchase_invoice_due_day').change(function() {
        if (this.value != '') {
            var date_invoice = new Date($('#purchase_invoice_date').val());
            date_invoice.setDate(date_invoice.getDate() + parseInt(this.value));
            var date_str = date_invoice.toISOString();
            var day = date_str.substring(8, 10);
            var month = date_str.substring(5, 7);
            var year = date_str.substring(0, 4);
            var due_date = year + '-' + month + '-' + day;

            $('#purchase_invoice_due_date').val(due_date);

            setTimeout(() => {
                function_elements_add('purchase_invoice_due_date', due_date);
            }, 100);
        } else {
            var date_invoice = new Date($('#purchase_invoice_date').val());
            date_invoice.setDate(date_invoice.getDate() + 0);
            var date_str = date_invoice.toISOString();
            var day = date_str.substring(8, 10);
            var month = date_str.substring(5, 7);
            var year = date_str.substring(0, 4);
            var due_date = year + '-' + month + '-' + day;

            $('#purchase_invoice_due_date').val(due_date);

            setTimeout(() => {
                function_elements_add('purchase_invoice_due_date', due_date);
            }, 100);
        }
    });
    $('#purchase_invoice_due_date').change(function() {
        var due_date = new Date(this.value);
        var date_invoice = new Date($('#purchase_invoice_date').val());
        var difference = due_date.getTime() - date_invoice.getTime();
        var due_day_date = difference / (1000 * 3600 * 24);

        $('#purchase_invoice_due_day').val(due_day_date);

        setTimeout(() => {
            function_elements_add('purchase_invoice_due_day', due_day_date);
        }, 100);
    });
    $("#discount_percentage").change(function() {
        calcPurchase();
        costChanged();
        checkIfCostIsChaged();
    });

    $("#discount_amount_view").change(function() {
        calcDiscountPercentagePurchase();
        checkIfCostIsChaged();
    });

    $("#paid_amount_view").change(function() {
        let paid_amount = parseInt(($("#paid_amount_view").val()||0));
        let total_amount = parseInt($("#total_amount").val());
        let owing_amount = paid_amount - total_amount;
        $('#paid_amount_view').val(toRp(paid_amount));
        $('#paid_amount').val(paid_amount);
        $("#owing_amount").val(Math.abs(owing_amount));
        $("#owing_amount_view").val(toRp(Math.abs(owing_amount)));
    });
    $('#purchase_payment_method').change(function() {
        if (this.value == 0) {
            $('#due_date').addClass('d-none');
        } else {
            $('#due_date').removeClass('d-none');
        }
    });
    if($("#item_packge_id").val()==null) {
        disableButton();
    }
    $("#profit_view").change(function (e) {
        $("#profit").val(this.value);
        $("#profit_view").val(numberFormat(this.value));
        calcMarginPercentage();
    });
    
    $('#margin_percentage').change(function() {
        calcModalMargin();
    });
    $('#item_price_new_view').change(function() {
        let price_new = parseInt($('#item_price_new_view').val());
        let cost_new = parseInt($('#item_cost_new').val());
        let margin_old = parseInt($('#margin_percentage_old').val());
        let margin = parseInt($('#margin_percentage').val());
        let margin_percentage = ((price_new - cost_new) / cost_new) * 100;
        console.log(price_new);
        // if (margin_percentage > 100) {
        //     alert('Margin tidak boleh melebihi 100%');
        //     $('#margin_percentage').val(margin_old);
        //     var amount_margin = cost_new + ((cost_new * margin_old) / 100);
        //     $('#item_price_new_view').val(toRp(amount_margin));
        //     $('#item_price_new').val(amount_margin);
        // } else {
        if (Number.isInteger(margin_percentage)) {
            $('#margin_percentage').val(margin_percentage);
        } else {
            $('#margin_percentage').val(margin_percentage.toFixed(2));
        }
        let profit = (price_new - cost_new);
        $('#profit').val(profit);
        $('#profit_view').val(numberFormat(profit));
        $('#item_price_new_view').val(toRp(price_new));
        $('#item_price_new').val(price_new);
        // }
    });
});
function resetCostChanged() {
    $("#reload-price-ico").hide();
    $("#change-price-view").hide();
    $("#item_unit_cost_view").val(number_format($("#item_unit_cost_ori").val()));
    $("#item_unit_cost").val($("#item_unit_cost_ori").val());
    $("#cost_is_changed").val(0);
}
function costChanged() {
    $("#reload-price-ico").show();
    $("#change-price-view").show();
    $("#cost_is_changed").val(1);
}
function disableButton() {
    $("#btn-tambah-purchase-item").addClass('disabled');
    $("#btn-tambah-purchase-item").attr("disabled", true);
}
function enableButton() {
    if ($("#quantity").val() != "" && $("#item_packge_id").val() != null) {
        $("#btn-tambah-purchase-item").attr("disabled", false);
        $("#btn-tambah-purchase-item").removeAttr("disabled");
        $("#btn-tambah-purchase-item").removeClass("disabled")
    }else{
        disableButton();
    }
}
function reset_add() {
    loading();
    $.ajax({
        type: "GET",
        url: route('add-reset-purchase-invoice'),
        success: function(msg) {
            loading();
            location.reload();
        }

    });
}
function calcModalMargin() {
    // calcModalWithoutMargin();
    let sbs = $("#item_cost_new").val();
    let margin = parseFloat($("#margin_percentage").val()||0);
    let profit = (sbs * margin) / 100;
    $("#profit").val(profit);
    $("#profit_view").val(numberFormat(profit));
    let price_new = parseFloat(sbs) + parseFloat(profit);
    $("#item_price_new_view").val(numberFormat(price_new));
    $("#item_price_new").val(price_new);
}
function calcMarginPercentage() {
    calcModalWithoutMargin();
    let profit = parseFloat($("#profit").val());
    let sbs = parseFloat($("#item_price_new_before_profit").val());
    let margin = (profit / sbs) * 100;
    $("#profit").val(profit);
    $("#profit_view").val(numberFormat(profit));
    let price_new = parseFloat(sbs) + parseFloat(profit);
    $("#item_price_new_view").val(numberFormat(price_new));
    $("#item_price_new").val(price_new);
    $("#margin_percentage").val(numberFormat(margin));
}
function calcModalWithoutMargin() {
    let dscprcn = $("#disc_change_modal_percentage").val();
}
function calcModalWithoutMargin() {
    let dscprcn = $("#disc_change_modal_percentage").val();
}
function calcPurchase() {
    let cost = parseFloat($("#item_unit_cost").val()) || 0;
    let qtyitm = parseFloat($("#quantity").val()) || 0;
    $("#subtotal_amount_view").val(numberFormat(cost * qtyitm));
    $("#subtotal_amount").val(cost * qtyitm);
    calcDiscountAmountPurchase();
    let discountAmount = parseFloat($("#discount_amount").val());
    let total = parseFloat($("#subtotal_amount").val()) - discountAmount;
    $("#subtotal_amount_after_discount_view").val(numberFormat(total));
    $("#subtotal_amount_after_discount").val(total);
}
function calcDiscountAmountPurchase() {
    let subtotal = parseFloat($("#subtotal_amount").val());
    let discount_percentage = parseFloat($("#discount_percentage").val()) || 0;
    let discount_amount = (subtotal * discount_percentage) / 100;
    $("#discount_amount_view").val(numberFormat(discount_amount));
    $("#discount_amount").val(discount_amount);
    let total_amount = subtotal - discount_amount;
    $("#subtotal_amount_after_discount_view").val(numberFormat(total_amount));
    $("#subtotal_amount_after_discount").val(total_amount);
}
function calcDiscountPercentagePurchase() {
    let subtotal = parseFloat($("#subtotal_amount").val());
    let discount_amount = parseFloat($("#discount_amount_view").val()) || 0;
    let total_amount = subtotal - discount_amount;
    $("#subtotal_amount_after_discount_view").val(numberFormat(total_amount));
    $("#subtotal_amount_after_discount").val(total_amount);
    let discount_percentage = (discount_amount / subtotal) * 100;
    $("#discount_percentage").val(discount_percentage.toFixed(2));
    $("#discount_amount").val(discount_amount);
    $("#discount_amount_view").val(numberFormat(discount_amount));
}
function checkIfCostIsChaged() { 
    let dscPrcn = $('#discount_percentage').val();
    let unitCost = $('#item_unit_cost').val();
    let ogUnitCost = $('#item_unit_cost_ori').val();
    if((dscPrcn == "" ||dscPrcn == 0 || dscPrcn == undefined)&&(unitCost==ogUnitCost)){
        resetCostChanged();
        return false;
    }else{
        costChanged();
        return true;
    }
 }
 function calcModalWithoutMargin() {
    let dscprcn = $("#disc_change_modal_percentage").val();
    let ppn =   (parseFloat($("#item_cost_old").val()) *
            parseFloat($("#tax_ppn_percentage").val())) /
        100;
    let disc =
        (parseFloat($("#item_cost_old").val() || 0) *
            parseFloat(dscprcn || 0)) /
        100;
    let cost = parseFloat($("#item_cost_new").val()||0);
    $("#ppn_change_modal_percentage").val(
        ppn + " (" + $("#tax_ppn_percentage").val() + "%)"
    );
    $("#discount_change_modal_percentage").val(
        disc + " (" + dscprcn + "%)"
    );
    let sbs = cost + ppn - disc;
    $("#item_price_new_before_profit").val(sbs);
    return sbs;
}
function check() {
    method = $("#purchase_payment_method").val();
    pay = parseInt($("#paid_amount").val());
    if (method == 0 && pay == 0) {
        alert("Kolom Dibayar Harus Diisi !");
        return 0;
    } else {
        $("#modal-confirm").modal("toggle");

        // checkIfCostChanged();
        $('#form-invoice').submit();
    }
}