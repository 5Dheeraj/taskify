$(document).ready(function () {
    var $menu = $("#dynamic-menu");
    if ($menu.length === 0) {
        return; // Exit if menu not found
    }
    $menu.addClass("d-none");
    var $menuItems = $menu.children("li");
    // Load usage counts and pinned states from localStorage
    var usageCounts = JSON.parse(localStorage.getItem("menuUsageCounts")) || {};
    var pinnedItems = JSON.parse(localStorage.getItem("menuPinnedItems")) || {};
    // Function to get a unique key for each menu item
    function getMenuItemKey($item) {
        if (!$item || !$item.length) {
            return null;
        }
        var text = $item.find("a div").text().trim();
        if (text) return text;
        var classes = $item.attr("class");
        if (classes) {
            var classList = classes.split(/\s+/);
            for (var i = 0; i < classList.length; i++) {
                if (classList[i] !== "menu-item" && classList[i] !== "active") {
                    return classList[i];
                }
            }
        }
        var href = $item.find("a").attr("href");
        if (href) return href;
        return null;
    }
    // Initialize counts and pinned states if not present
    $menuItems.each(function () {
        var key = getMenuItemKey($(this));
        if (key) {
            if (!(key in usageCounts)) {
                usageCounts[key] = 0;
            }
            if (!(key in pinnedItems)) {
                pinnedItems[key] = false;
            }
        }
    });
    // Function to reorder menu items
    function reorderMenu() {
        $menu.css("visibility", "hidden");
        var pinnedMenuItems = $menuItems
            .filter(function () {
                var key = getMenuItemKey($(this));
                return key && pinnedItems[key];
            })
            .detach();
        var unpinnedMenuItems = $menuItems
            .filter(function () {
                var key = getMenuItemKey($(this));
                return key && !pinnedItems[key];
            })
            .detach()
            .sort(function (a, b) {
                var keyA = getMenuItemKey($(a));
                var keyB = getMenuItemKey($(b));
                return keyA && keyB
                    ? (usageCounts[keyB] || 0) - (usageCounts[keyA] || 0)
                    : 0;
            });
        $menu
            .append(pinnedMenuItems)
            .append(unpinnedMenuItems)
            .css("visibility", "visible");
    }
    // Initial ordering
    reorderMenu();
    // Add click event listeners to menu items
    $menuItems.find("> a").on("click", function (e) {
        var key = getMenuItemKey($(this).parent());
        if (key) {
            usageCounts[key] = (usageCounts[key] || 0) + 1;
            localStorage.setItem(
                "menuUsageCounts",
                JSON.stringify(usageCounts)
            );
        }
    });
    // Add pin/unpin functionality
    $menuItems.each(function () {
        var $item = $(this);
        var key = getMenuItemKey($item);
        var $menuLink = $item.find("> a.menu-link");
        var $pinIcon = $('<i class="bx bx-pin pin-icon"></i>').appendTo(
            $menuLink
        );
        $pinIcon.on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            pinnedItems[key] = !pinnedItems[key];
            localStorage.setItem(
                "menuPinnedItems",
                JSON.stringify(pinnedItems)
            );
            $item.toggleClass("pinned");
            $(this).toggleClass("bx-pin").toggleClass("bxs-pin  text-warning");
            reorderMenu();
        });
        if (pinnedItems[key]) {
            $item.addClass("pinned");
            $pinIcon.removeClass("bx-pin").addClass("bxs-pin  text-warning");
        }
    });
    // Implement a decay mechanism
    function decayUsageCounts() {
        var changed = false;
        $.each(usageCounts, function (key, value) {
            if (value > 0 && !pinnedItems[key]) {
                usageCounts[key] = Math.max(0, value - 1);
                changed = true;
            }
        });
        if (changed) {
            localStorage.setItem(
                "menuUsageCounts",
                JSON.stringify(usageCounts)
            );
            reorderMenu();
        }
    }
    // Call decay function daily
    var DECAY_INTERVAL = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
    var lastDecayTime = localStorage.getItem("lastDecayTime") || 0;
    var currentTime = Date.now();
    if (currentTime - lastDecayTime > DECAY_INTERVAL) {
        decayUsageCounts();
        localStorage.setItem("lastDecayTime", currentTime.toString());
    }
    $menu.css("opacity", 0).removeClass("d-none").animate({ opacity: 1 }, 500);
});
("use strict");
toastr.options = {
    positionClass: toastPosition,
    timeOut: parseFloat(toastTimeOut) * 1000,
    showDuration: "300",
    hideDuration: "1000",
    extendedTimeOut: "1000",
    progressBar: true,
    closeButton: true,
};
var urlPrefix = window.location.pathname.split("/")[1];
$(document).ready(function () {
    $(
        ".js-example-basic-multiple,#plan_id , #filter_plans ,#filter_by_users , #user_id"
    ).select2();
});
$(document).ready(function () {
    $(".js-example-basic-multiple").select2();
});
$(document).on("click", ".delete", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var type = $(this).data("type");

    var reload = $(this).data("reload") === true; // Simplified the reload condition
    var tableID = $(this).data("table") || "table";
    // Map types to their respective actions and API routes
    var actions = {
        users: "delete_user",
        "contract-type": "delete-contract-type",
        "project-media": "delete-media",
        "task-media": "delete-media",
        "expense-type": "delete-expense-type",
        milestone: "delete-milestone",
        default: "destroy",
    };
    var typeMappings = {
        "contract-type": "contracts",
        "project-media": "projects",
        "task-media": "tasks",
        "expense-type": "expenses",
        milestone: "projects",
        default: type,
    };
    var destroy = actions[type] || actions["default"];
    type = typeMappings[type] || typeMappings["default"];
    var urlPrefix = window.location.pathname.split("/")[1];

    // Show confirmation modal
    $("#deleteModal").modal("show");
    $("#deleteModal").off("click", "#confirmDelete");
    // On confirmation click
    $("#deleteModal").on("click", "#confirmDelete", function () {
        $("#confirmDelete").html(label_please_wait).attr("disabled", true);
        // Perform the AJAX request
        $.ajax({
            url: `/${urlPrefix}/${type}/${destroy}/${id}`,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').val(), // Simplified CSRF token retrieval
            },
            success: function (response) {
                $("#confirmDelete").html(label_yes).attr("disabled", false);
                $("#deleteModal").modal("hide");
                if (!response.error) {
                    toastr.success(response.message);
                    if (reload) {
                        location.reload();
                    } else if (tableID) {
                        $("#" + tableID).bootstrapTable("refresh");
                    } else {
                        location.reload();
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function () {
                $("#confirmDelete").html(label_yes).attr("disabled", false);
                $("#deleteModal").modal("hide");
                toastr.error(label_something_went_wrong);
            },
        });
    });
});
$(document).on("click", ".delete-selected", function (e) {
    e.preventDefault();
    const $this = $(this);
    const table = $this.data("table");
    const type = $this.data("type");
    const destroyActionMap = {
        users: "delete_multiple_user",
        "contract-type": "delete-multiple-contract-type",
        "project-media": "delete-multiple-media",
        "task-media": "delete-multiple-media",
        "expense-type": "delete-multiple-expense-type",
        milestone: "delete-multiple-milestone",
        default: "destroy_multiple",
    };
    const typeMap = {
        "contract-type": "contracts",
        "project-media": "projects",
        "task-media": "tasks",
        "expense-type": "expenses",
        milestone: "projects",
    };
    const destroy = destroyActionMap[type] || destroyActionMap.default;
    const mappedType = typeMap[type] || type;
    const urlPrefix = window.location.pathname.split("/")[1];
    const selections = $(`#${table}`).bootstrapTable("getSelections");
    const selectedIds = selections.map((row) => row.id);
    if (selectedIds.length === 0) {
        return toastr.error(label_please_select_records_to_delete);
    }
    $("#confirmDeleteSelectedModal")
        .modal("show")
        .off("click", "#confirmDeleteSelections")
        .on("click", "#confirmDeleteSelections", function () {
            const $confirmBtn = $("#confirmDeleteSelections");
            $confirmBtn.html(label_please_wait).prop("disabled", true);
            $.ajax({
                url: `/${urlPrefix}/${mappedType}/${destroy}/`,
                type: "DELETE",
                data: { ids: selectedIds },
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').val(),
                },
                complete: () => {
                    $confirmBtn.html(label_yes).prop("disabled", false);
                    $("#confirmDeleteSelectedModal").modal("hide");
                },
                success: function (response) {
                    $(`#${table}`).bootstrapTable("refresh");
                    toastr[response.error ? "error" : "success"](
                        response.message
                    );
                },
                error: () => toastr.error(label_something_went_wrong),
            });
        });
});
function update_status(e) {
    var id = e["id"];
    var name = e["name"];
    var reload = e.getAttribute("reload") ? true : false;
    var status;
    var is_checked = $("input[name=" + name + "]:checked");
    if (is_checked.length >= 1) {
        status = 1;
    } else {
        status = 0;
    }
    $.ajax({
        url: "/master-panel/todos/update_status",
        type: "POST", // Use POST method
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        data: {
            _method: "PUT", // Specify the desired method
            id: id,
            status: status,
        },
        success: function (response) {
            if (response.error == false) {
                if (reload) {
                    location.reload();
                }
                toastr.success(response.message); // show a success message
                $("#" + id + "_title").toggleClass("striked");
            } else {
                toastr.error(response.message);
            }
        },
    });
}
$(document).on("click", ".edit-todo", function () {
    var id = $(this).data("id");
    var url = $(this).data("url");
    $("#edit_todo_modal").modal("show");
    $.ajax({
        url: "/master-panel/todos/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#todo_id").val(response.todo.id);
            $("#todo_title").val(response.todo.title);
            $("#todo_priority").val(response.todo.priority);
            $("#todo_description").val(response.todo.description);
        },
    });
});
$(document).on("click", ".edit-note", function () {
    var id = $(this).data("id");
    $("#edit_note_modal").modal("show");
    var url = $(this).data("url");
    var classes = $("#note_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    $.ajax({
        url: url,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#note_id").val(response.note.id);
            $("#note_title").val(response.note.title);
            $("#note_color")
                .val(response.note.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.note.color);
            var description =
                response.note.description !== null
                    ? response.note.description
                    : "";
            $("#edit_note_modal").find("#note_description").val(description);
        },
    });
});
$(document).on("click", ".edit-status", function () {
    var id = $(this).data("id");
    var routePrefix = $("#table").data("routePrefix");
    $("#edit_status_modal").modal("show");
    var classes = $("#status_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    $.ajax({
        url: routePrefix + "/status/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#status_id").val(response.status.id);
            $("#status_title").val(response.status.title);
            $("#status_color")
                .val(response.status.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.status.color);
            var modalForm = $("#edit_status_modal").find("form");
            var usersSelect = modalForm.find(
                '.js-example-basic-multiple[name="role_ids[]"]'
            );
            usersSelect.val(response.roles);
            usersSelect.trigger("change"); // Trigger change event to update select2
        },
    });
});
$(document).on("click", ".edit-tag", function () {
    var id = $(this).data("id");
    var routePrefix = $("#table").data("routePrefix");
    var classes = $("#tag_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    $("#edit_tag_modal").modal("show");
    $.ajax({
        url: routePrefix + "/tags/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#tag_id").val(response.tag.id);
            $("#tag_title").val(response.tag.title);
            $("#tag_color")
                .val(response.tag.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.tag.color);
        },
    });
});
$(document).on("click", ".edit-leave-request", function () {
    var id = $(this).data("id");
    $("#edit_leave_request_modal").modal("show");
    $.ajax({
        url: "/master-panel/leave-requests/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedFromDate = moment(response.lr.from_date).format(
                js_date_format
            );
            var formattedToDate = moment(response.lr.to_date).format(
                js_date_format
            );
            var fromDateSelect = $("#edit_leave_request_modal").find(
                "#update_start_date"
            );
            var toDateSelect = $("#edit_leave_request_modal").find(
                "#update_end_date"
            );
            var reasonSelect = $("#edit_leave_request_modal").find(
                '[name="reason"]'
            );
            var totalDaysSelect = $("#edit_leave_request_modal").find(
                "#update_total_days"
            );
            $("#lr_id").val(response.lr.id);
            $("#leaveUser").val(
                response.lr.user.first_name + " " + response.lr.user.last_name
            );
            fromDateSelect.val(formattedFromDate);
            toDateSelect.val(formattedToDate);
            initializeDateRangePicker("#update_start_date,#update_end_date");
            var start_date = moment(fromDateSelect.val(), js_date_format);
            var end_date = moment(toDateSelect.val(), js_date_format);
            var total_days = end_date.diff(start_date, "days") + 1;
            totalDaysSelect.val(total_days);
            if (response.lr.from_time && response.lr.to_time) {
                $("#updatePartialLeave")
                    .prop("checked", true)
                    .trigger("change");
                var fromTimeSelect = $("#edit_leave_request_modal").find(
                    '[name="from_time"]'
                );
                var toTimeSelect = $("#edit_leave_request_modal").find(
                    '[name="to_time"]'
                );
                fromTimeSelect.val(response.lr.from_time);
                toTimeSelect.val(response.lr.to_time);
            } else {
                $("#updatePartialLeave")
                    .prop("checked", false)
                    .trigger("change");
            }
            if (response.lr.visible_to_all) {
                $("#edit_leave_request_modal")
                    .find(".leaveVisibleToAll")
                    .prop("checked", true)
                    .trigger("change");
            } else {
                $("#edit_leave_request_modal")
                    .find(".leaveVisibleToAll")
                    .prop("checked", false)
                    .trigger("change");
                var visibleToSelect = $("#edit_leave_request_modal").find(
                    '.js-example-basic-multiple[name="visible_to_ids[]"]'
                );
                var visibleToUsers = response.visibleTo.map((user) => user.id);
                visibleToSelect.val(visibleToUsers);
                visibleToSelect.trigger("change");
            }
            reasonSelect.val(response.lr.reason);
            $("input[name=status][value=" + response.lr.status + "]").prop(
                "checked",
                true
            );
        },
    });
});
$(document).on("click", ".edit-contract-type", function () {
    var routePrefix = $("#table").data("routePrefix");
    var id = $(this).data("id");
    $("#edit_contract_type_modal").modal("show");
    $.ajax({
        url: "" + routePrefix + "/contracts/get-contract-type/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#update_contract_type_id").val(response.ct.id);
            $("#contract_type").val(response.ct.type);
        },
    });
});
$(document).on("click", ".edit-contract", function () {
    var id = $(this).data("id");
    var routePrefix = $("#contracts_table").data("routePrefix");
    $("#edit_contract_modal").modal("show");
    $.ajax({
        url: "/master-panel/contracts/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            if (response.error == false) {
                var formattedStartDate = moment(
                    response.contract.start_date
                ).format(js_date_format);
                var formattedEndDate = moment(
                    response.contract.end_date
                ).format(js_date_format);
                $("#contract_id").val(response.contract.id);
                $("#title").val(response.contract.title);
                $("#value").val(response.contract.value);
                $("#client_id").val(response.contract.client_id);
                $("#project_id").val(response.contract.project_id);
                $("#contract_type_id").val(response.contract.contract_type_id);
                $("#update_contract_description").val(
                    response.contract.description
                );
                $("#update_start_date").val(formattedStartDate);
                $("#update_end_date").val(formattedEndDate);
                initializeDateRangePicker(
                    "#update_start_date, #update_end_date"
                );
            } else {
                location.reload();
            }
        },
    });
});
function initializeDateRangePicker(inputSelector) {
    // Find the modal that contains the input field
    var modalId = $(inputSelector).closest(".modal").attr("id");
    // Initialize daterangepicker and bind it to the modal using the modal ID
    $(inputSelector).daterangepicker({
        alwaysShowCalendars: true,
        showCustomRangeLabel: true,
        minDate: moment($(inputSelector).val(), js_date_format),
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: true,
        parentEl: "#" + modalId, // Attach the daterangepicker to the modal
        locale: {
            cancelLabel: "Clear",
            format: js_date_format,
        },
    });
    // Optional: Reposition on modal scroll
    $("#" + modalId).on("scroll", function () {
        if ($(inputSelector).data("daterangepicker")) {
            $(inputSelector).data("daterangepicker").hide();
            $(inputSelector).data("daterangepicker").show();
        }
    });
}
$(document).on("click", "#set-as-default", function (e) {
    e.preventDefault();
    var lang = $(this).data("lang");
    var url = $(this).data("url");
    $("#default_language_modal").modal("show"); // show the confirmation modal
    $("#default_language_modal").on("click", "#confirm", function () {
        $.ajax({
            url: url,
            type: "PUT",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
            },
            data: {
                lang: lang,
            },
            success: function (response) {
                if (response.error == false) {
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
        });
    });
});
$(document).on("click", "#remove-participant", function (e) {
    e.preventDefault();
    var routePrefix = $(this).data("routePrefix");
    $("#leaveWorkspaceModal").modal("show"); // show the confirmation modal
    $("#leaveWorkspaceModal").on("click", "#confirm", function () {
        $.ajax({
            url: routePrefix + "/workspaces/remove_participant",
            type: "GET",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
            },
            success: function (response) {
                location.reload();
            },
            error: function (data) {
                location.reload();
            },
        });
    });
});
$(document).ready(function () {
    // Define the IDs you want to process
    var idsToProcess = [
        "#start_date",
        "#end_date",
        "#update_start_date",
        "#update_end_date",
        "#lr_end_date",
        "#meeting_end_date",
        "#expense_date",
        "#update_expense_date",
        "#payment_date",
        "#update_payment_date",
        "#update_milestone_start_date",
        "#update_milestone_end_date",
        "#task_start_date",
        "#task_end_date",
        "#edit_announcement_start_date",
        "#edit_announcement_end_date",
    ];
    // Loop through the IDs
    for (var i = 0; i < idsToProcess.length; i++) {
        var id = idsToProcess[i];
        if ($(id).length) {
            if (
                id === "#payment_date" &&
                !$(id).closest("#create_payment_modal").length
            ) {
                continue;
            }
            if ($(id).val() == "") {
                $(id).val(moment(new Date()).format(js_date_format));
            }
            var modalId = $(id).closest(".modal").attr("id");
            $(id).daterangepicker({
                alwaysShowCalendars: true,
                showCustomRangeLabel: true,
                // minDate: moment($(id).val(), js_date_format),
                parentEl: "#" + modalId, // Attach the daterangepicker to the modal
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                locale: {
                    cancelLabel: "Clear",
                    format: js_date_format,
                },
                function(start, end, label) {
                    // Replace Font Awesome/Glyphicon icons with Boxicons
                    $(".daterangepicker .prev").html(
                        '<i class="bx bx-chevron-left"></i>'
                    );
                    $(".daterangepicker .next").html(
                        '<i class="bx bx-chevron-right"></i>'
                    );
                },
            });
        }
    }
    // Define the IDs you want to process
    var idsToProcess = ["#payment_date", "#dob", "#doj"];
    var minDateStr = "01/01/1950";
    var minDate = moment(minDateStr, "DD/MM/YYYY");
    // Loop through the IDs
    for (var i = 0; i < idsToProcess.length; i++) {
        var id = idsToProcess[i];
        if ($(id).length) {
            var modalId = $(id).closest(".modal").attr("id");
            $(id).daterangepicker({
                alwaysShowCalendars: true,
                showCustomRangeLabel: true,
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                minDate: minDate,
                parentEl: "#" + modalId,
                locale: {
                    cancelLabel: "Clear",
                    format: js_date_format,
                },
            });
            $(id).on("apply.daterangepicker", function (ev, picker) {
                // Update the input with the selected date
                $(this).val(picker.startDate.format(js_date_format));
            });
        }
    }
});
if ($("#total_days").length) {
    $("#end_date").on("apply.daterangepicker", function (ev, picker) {
        // Calculate the inclusive difference in days between start_date and end_date
        var start_date = moment($("#start_date").val(), js_date_format);
        var end_date = picker.startDate;
        var total_days = end_date.diff(start_date, "days") + 1;
        // Display the total_days in the total_days input field
        $("#total_days").val(total_days);
    });
}
$(document).ready(function () {
    $(
        "#report_start_date_between,#report_end_date_between,#filter_date_range,#project_start_date_between,#project_end_date_between,#task_start_date_between,#task_end_date_between,#lr_start_date_between,#lr_end_date_between,#contract_start_date_between,#contract_end_date_between,#timesheet_start_date_between,#timesheet_end_date_between,#meeting_start_date_between,#meeting_end_date_between,#activity_log_between_date,#start_date_between,#end_date_between,#expense_from_date_between"
    ).daterangepicker({
        alwaysShowCalendars: true,
        showCustomRangeLabel: true,
        singleDatePicker: false,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: {
            cancelLabel: "Clear",
            format: js_date_format,
        },
    });
    $(
        "#report_start_date_between,#report_end_date_between,#filter_date_range,#project_start_date_between,#project_end_date_between,#task_start_date_between,#task_end_date_between,#lr_start_date_between,#lr_end_date_between,#contract_start_date_between,#contract_end_date_between,#timesheet_start_date_between,#timesheet_end_date_between,#meeting_start_date_between,#meeting_end_date_between,#activity_log_between_date,#start_date_between,#end_date_between,#expense_from_date_between"
    ).on("apply.daterangepicker", function (ev, picker) {
        $(this).val(
            picker.startDate.format(js_date_format) +
            " To " +
            picker.endDate.format(js_date_format)
        );
    });
});
if ($("#project_start_date_between").length) {
    $("#project_start_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#project_start_date_from").val(startDate);
            $("#project_start_date_to").val(endDate);
            $("#projects_table").bootstrapTable("refresh");
        }
    );
    $("#project_start_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#project_start_date_from").val("");
            $("#project_start_date_to").val("");
            $("#projects_table").bootstrapTable("refresh");
            $("#project_start_date_between").val("");
        }
    );
    $("#project_end_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#project_end_date_from").val(startDate);
            $("#project_end_date_to").val(endDate);
            $("#projects_table").bootstrapTable("refresh");
        }
    );
    $("#project_end_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#project_end_date_from").val("");
            $("#project_end_date_to").val("");
            $("#projects_table").bootstrapTable("refresh");
            $("#project_end_date_between").val("");
        }
    );
}
if ($("#task_start_date_between").length) {
    $("#task_start_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#task_start_date_from").val(startDate);
            $("#task_start_date_to").val(endDate);
            $("#task_table").bootstrapTable("refresh");
        }
    );
    $("#task_start_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#task_start_date_from").val("");
            $("#task_start_date_to").val("");
            $("#task_table").bootstrapTable("refresh");
            $("#task_start_date_between").val("");
        }
    );
    $("#task_end_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#task_end_date_from").val(startDate);
            $("#task_end_date_to").val(endDate);
            $("#task_table").bootstrapTable("refresh");
        }
    );
    $("#task_end_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#task_end_date_from").val("");
            $("#task_end_date_to").val("");
            $("#task_table").bootstrapTable("refresh");
            $("#task_end_date_between").val("");
        }
    );
}
if ($("#timesheet_start_date_between").length) {
    $("#timesheet_start_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#timesheet_start_date_from").val(startDate);
            $("#timesheet_start_date_to").val(endDate);
            $("#timesheet_table").bootstrapTable("refresh");
        }
    );
    $("#timesheet_start_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#timesheet_start_date_from").val("");
            $("#timesheet_start_date_to").val("");
            $("#timesheet_table").bootstrapTable("refresh");
            $("#timesheet_start_date_between").val("");
        }
    );
    $("#timesheet_end_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#timesheet_end_date_from").val(startDate);
            $("#timesheet_end_date_to").val(endDate);
            $("#timesheet_table").bootstrapTable("refresh");
        }
    );
    $("#timesheet_end_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#timesheet_end_date_from").val("");
            $("#timesheet_end_date_to").val("");
            $("#timesheet_table").bootstrapTable("refresh");
            $("#timesheet_end_date_between").val("");
        }
    );
}
if ($("#meeting_start_date_between").length) {
    $("#meeting_start_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#meeting_start_date_from").val(startDate);
            $("#meeting_start_date_to").val(endDate);
            $("#meetings_table").bootstrapTable("refresh");
        }
    );
    $("#meeting_start_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#meeting_start_date_from").val("");
            $("#meeting_start_date_to").val("");
            $("#meetings_table").bootstrapTable("refresh");
            $("#meeting_start_date_between").val("");
        }
    );
    $("#meeting_end_date_between").on(
        "apply.daterangepicker",
        function (ev, picker) {
            var startDate = picker.startDate.format("YYYY-MM-DD");
            var endDate = picker.endDate.format("YYYY-MM-DD");
            $("#meeting_end_date_from").val(startDate);
            $("#meeting_end_date_to").val(endDate);
            $("#meetings_table").bootstrapTable("refresh");
        }
    );
    $("#meeting_end_date_between").on(
        "cancel.daterangepicker",
        function (ev, picker) {
            $("#meeting_end_date_from").val("");
            $("#meeting_end_date_to").val("");
            $("#meetings_table").bootstrapTable("refresh");
            $("#meeting_end_date_between").val("");
        }
    );
}
$(
    "textarea#footer_text,textarea#contract_description,textarea#update_contract_description , #privacy_policy , #terms_and_conditions , #refund_policy , #company_address,.description"
).tinymce({
    height: 250,
    menubar: false,
    plugins: [
        "link",
        "a11ychecker",
        "advlist",
        "advcode",
        "advtable",
        "autolink",
        "checklist",
        "export",
        "lists",
        "link",
        "image",
        "charmap",
        "preview",
        "anchor",
        "searchreplace",
        "visualblocks",
        "powerpaste",
        "fullscreen",
        "formatpainter",
        "insertdatetime",
        "media",
        "table",
        "help",
        "wordcount",
    ],
    toolbar:
        "link | undo redo | a11ycheck casechange blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | removeformat | code table help",
});
$(document).on("submit", ".form-submit-event", function (e) {
    e.preventDefault();
    if ($("#net_payable").length > 0) {
        var net_payable = $("#net_payable").text();
        $("#net_pay").val(net_payable);
    }
    var formData = new FormData(this);
    var currentForm = $(this);
    var parentModal = $(this).closest(".modal");
    var submit_btn = $(this).find("#submit_btn");
    if (submit_btn.length == 0) {
        submit_btn = parentModal.find('button[type="submit"]');
    }
    // If still no submit button found, check within the form
    if (submit_btn.length === 0) {
        submit_btn = currentForm.find('button[type="submit"]');
    }
    var btn_html = submit_btn.html();
    var btn_val = submit_btn.val();
    var redirect_url = currentForm.find('input[name="redirect_url"]').val();
    redirect_url =
        typeof redirect_url !== "undefined" && redirect_url ? redirect_url : "";
    var button_text =
        btn_html != "" || btn_html != "undefined" ? btn_html : btn_val;
    var tableInput = currentForm.find('input[name="table"]');
    var tableID = tableInput.length ? tableInput.val() : "table";
    $.ajax({
        type: "POST",
        url: $(this).attr("action"),
        data: formData,
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        beforeSend: function () {
            submit_btn.html(label_please_wait);
            submit_btn.attr("disabled", true);
        },
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (result) {
            submit_btn.html(button_text);
            submit_btn.attr("disabled", false);
            if (result["error"] == true) {
                toastr.error(result["message"]);
            } else {
                if ($(".empty-state").length > 0) {
                    if (result.hasOwnProperty("message")) {
                        toastr.success(result["message"]);
                        // Show toastr for 3 seconds before reloading or redirecting
                        setTimeout(handleRedirection, 3000);
                    } else {
                        handleRedirection();
                    }
                } else {
                    if (currentForm.find('input[name="dnr"]').length > 0) {
                        var modalWithClass = $(".modal.fade.show");
                        if (modalWithClass.length > 0) {
                            var idOfModal = modalWithClass.attr("id");
                            $("#" + idOfModal).modal("hide");
                            $("#" + tableID).bootstrapTable("refresh");
                            currentForm[0].reset();
                            var partialLeaveCheckbox = $("#partialLeave");
                            if (partialLeaveCheckbox.length) {
                                partialLeaveCheckbox.trigger("change");
                            }
                            resetDateFields(currentForm);
                            if (idOfModal == "create_status_modal") {
                                var dropdownSelector = modalWithClass.find(
                                    'select[name="status_id"]'
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.status;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("data-color", newItem.color)
                                        .attr("selected", true)
                                        .text(
                                            newItem.title +
                                            " (" +
                                            newItem.color +
                                            ")"
                                        );
                                    $(dropdownSelector).append(newOption);
                                    var openModalId = dropdownSelector
                                        .closest(".modal.fade.show")
                                        .attr("id");
                                    // List of all possible modal IDs
                                    var modalIds = [
                                        "#create_project_modal",
                                        "#edit_project_modal",
                                        "#create_task_modal",
                                        "#edit_task_modal",
                                    ];
                                    // Iterate through each modal ID
                                    modalIds.forEach(function (modalId) {
                                        // If the modal ID is not the open one
                                        if (modalId !== "#" + openModalId) {
                                            // Find the select element within the modal
                                            var otherModalSelector = $(
                                                modalId
                                            ).find('select[name="status_id"]');
                                            // Create a new option without 'selected' attribute
                                            var otherOption = $(
                                                "<option></option>"
                                            )
                                                .attr("value", newItem.id)
                                                .attr(
                                                    "data-color",
                                                    newItem.color
                                                )
                                                .text(
                                                    newItem.title +
                                                    " (" +
                                                    newItem.color +
                                                    ")"
                                                );
                                            // Append the option to the select element in the modal
                                            otherModalSelector.append(
                                                otherOption
                                            );
                                        }
                                    });
                                }
                            }
                            if (idOfModal == "create_priority_modal") {
                                var dropdownSelector = modalWithClass.find(
                                    'select[name="priority_id"]'
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.priority;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr(
                                            "class",
                                            "badge bg-label-" + newItem.color
                                        )
                                        .attr("selected", true)
                                        .text(
                                            newItem.title +
                                            " (" +
                                            newItem.color +
                                            ")"
                                        );
                                    $(dropdownSelector).append(newOption);
                                    var openModalId = dropdownSelector
                                        .closest(".modal.fade.show")
                                        .attr("id");
                                    // List of all possible modal IDs
                                    var modalIds = [
                                        "#create_project_modal",
                                        "#edit_project_modal",
                                        "#create_task_modal",
                                        "#edit_task_modal",
                                    ];
                                    // Iterate through each modal ID
                                    modalIds.forEach(function (modalId) {
                                        // If the modal ID is not the open one
                                        if (modalId !== "#" + openModalId) {
                                            // Find the select element within the modal
                                            var otherModalSelector = $(
                                                modalId
                                            ).find(
                                                'select[name="priority_id"]'
                                            );
                                            // Create a new option without 'selected' attribute
                                            var otherOption = $(
                                                "<option></option>"
                                            )
                                                .attr("value", newItem.id)
                                                .attr(
                                                    "class",
                                                    "badge bg-label-" +
                                                    newItem.color
                                                )
                                                .text(
                                                    newItem.title +
                                                    " (" +
                                                    newItem.color +
                                                    ")"
                                                );
                                            // Append the option to the select element in the modal
                                            otherModalSelector.append(
                                                otherOption
                                            );
                                        }
                                    });
                                }
                            }
                            if (idOfModal == "create_tag_modal") {
                                var dropdownSelector = modalWithClass.find(
                                    'select[name="tag_ids[]"]'
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.tag;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("data-color", newItem.color)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                    $(dropdownSelector).trigger("change");
                                    var openModalId = dropdownSelector
                                        .closest(".modal.fade.show")
                                        .attr("id");
                                    // List of all possible modal IDs
                                    var modalIds = [
                                        "#create_project_modal",
                                        "#edit_project_modal",
                                    ];
                                    // Iterate through each modal ID
                                    modalIds.forEach(function (modalId) {
                                        // If the modal ID is not the open one
                                        if (modalId !== "#" + openModalId) {
                                            // Find the select element within the modal
                                            var otherModalSelector = $(
                                                modalId
                                            ).find('select[name="tag_ids[]"]');
                                            // Create a new option without 'selected' attribute
                                            var otherOption = $(
                                                "<option></option>"
                                            )
                                                .attr("value", newItem.id)
                                                .attr(
                                                    "data-color",
                                                    newItem.color
                                                )
                                                .text(newItem.title);
                                            // Append the option to the select element in the modal
                                            otherModalSelector.append(
                                                otherOption
                                            );
                                        }
                                    });
                                }
                            }
                            if (idOfModal == "create_item_modal") {
                                var dropdownSelector = $("#item_id");
                                if (dropdownSelector.length) {
                                    var newItem = result.item;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                    $(dropdownSelector).trigger("change");
                                }
                            }
                            if (idOfModal === "create_contract_type_modal") {
                                var dropdownSelector = modalWithClass.find(
                                    'select[name="contract_type_id"]'
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.ct;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.type);
                                    // Append and select the new option in the current modal
                                    dropdownSelector.append(newOption);
                                    var openModalId = dropdownSelector
                                        .closest(".modal.fade.show")
                                        .attr("id");
                                    var otherModalId =
                                        openModalId === "create_contract_modal"
                                            ? "#edit_contract_modal"
                                            : "#create_contract_modal";
                                    var otherModalSelector = $(
                                        otherModalId
                                    ).find('select[name="contract_type_id"]');
                                    // Create a new option for the other modal without 'selected' attribute
                                    var otherOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .text(newItem.type);
                                    // Append the option to the other modal
                                    otherModalSelector.append(otherOption);
                                }
                            }
                            if (idOfModal == "create_pm_modal") {
                                var dropdownSelector = $(
                                    'select[name="payment_method_id"]'
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.pm;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                    $(dropdownSelector).trigger("change");
                                }
                            }
                            if (idOfModal == "create_allowance_modal") {
                                var dropdownSelector = $(
                                    'select[name="allowance_id"]'
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.allowance;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                    $(dropdownSelector).trigger("change");
                                }
                            }
                            if (idOfModal == "create_deduction_modal") {
                                var dropdownSelector = $(
                                    'select[name="deduction_id"]'
                                );
                                if (dropdownSelector.length) {
                                    var newItem = result.deduction;
                                    var newOption = $("<option></option>")
                                        .attr("value", newItem.id)
                                        .attr("selected", true)
                                        .text(newItem.title);
                                    $(dropdownSelector).append(newOption);
                                    $(dropdownSelector).trigger("change");
                                }
                            }
                        }
                        toastr.success(result["message"]);
                    } else {
                        if (result.hasOwnProperty("message")) {
                            toastr.success(result["message"]);
                            // Show toastr for 3 seconds before reloading or redirecting
                            setTimeout(handleRedirection, 3000);
                        } else {
                            handleRedirection();
                        }
                    }
                }
            }
        },
        error: function (xhr, status, error) {
            submit_btn.html(button_text);
            submit_btn.attr("disabled", false);
            if (xhr.status === 422) {
                // Handle validation errors here
                var response = xhr.responseJSON; // Assuming you're returning JSON
                if (response.error) {
                    // Handle the general error message
                    var generalErrorMessage = response.message;
                    toastr.error(generalErrorMessage);
                }
                // You can access validation errors from the response object
                var errors = response.errors;
                for (var key in errors) {
                    if (
                        errors.hasOwnProperty(key) &&
                        Array.isArray(errors[key])
                    ) {
                        errors[key].forEach(function (error) {
                            toastr.error(error);
                        });
                    }
                }
                // Example: Display the first validation error message
                toastr.error(label_please_correct_errors);
                // Assuming you have a list of all input fields with error messages
                var inputFields = currentForm.find(
                    "input[name], select[name], textarea[name]"
                );
                inputFields = $(inputFields.toArray().reverse());
                // Iterate through all input fields
                inputFields.each(function () {
                    var inputField = $(this);
                    var fieldName = inputField.attr("name");
                    var errorMessageElement;
                    if (errors && errors[fieldName]) {
                        if (inputField.closest(".input-group").length) {
                            errorMessageElement = inputField
                                .closest(".input-group")
                                .next(".error-message");
                            if (errorMessageElement.length === 0) {
                                errorMessageElement = $(
                                    '<span class="text-danger error-message"></span>'
                                );
                                inputField
                                    .closest(".input-group")
                                    .after(errorMessageElement);
                            }
                        } else {
                            errorMessageElement = inputField.next(
                                ".text-danger.error-message"
                            );
                            if (errorMessageElement.length === 0) {
                                errorMessageElement = $(
                                    '<span class="text-danger error-message"></span>'
                                );
                                inputField.after(errorMessageElement);
                            }
                        }
                        errorMessageElement.text(errors[fieldName][0]);
                        inputField[0].scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                        inputField.focus();
                    } else {
                        // If there is no validation error message, clear the existing message
                        errorMessageElement = inputField.next(".error-message");
                        if (errorMessageElement.length === 0) {
                            errorMessageElement = inputField
                                .parent()
                                .nextAll(".error-message")
                                .first();
                        }
                        if (
                            errorMessageElement &&
                            errorMessageElement.length > 0
                        ) {
                            errorMessageElement.remove();
                        }
                    }
                });
            } else {
                // Handle other errors (non-validation errors) here
                toastr.error(error);
            }
        },
    });
    function handleRedirection() {
        if (redirect_url === "") {
            window.location.reload(); // Reload the current page
        } else {
            window.location.href = redirect_url; // Redirect to specified URL
        }
    }
});
// Click event handler for the favorite icon
$(document).on("click", ".favorite-icon", function () {
    var icon = $(this);
    var routePrefix = $(this).data("routePrefix");
    var projectId = $(this).data("id");
    var isFavorite = icon.attr("data-favorite");
    isFavorite = isFavorite == 1 ? 0 : 1;
    var reload = $(this).data("require_reload") !== undefined ? 1 : 0;
    var dataTitle = icon.data("bs-original-title");
    var temp = dataTitle !== undefined ? "data-bs-original-title" : "title";
    // Send an AJAX request to update the favorite status
    $.ajax({
        url: "/master-panel/projects/update-favorite/" + projectId,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            is_favorite: isFavorite,
        },
        success: function (response) {
            if (reload) {
                location.reload();
            } else {
                icon.attr("data-favorite", isFavorite);
                // Update the tooltip text
                if (isFavorite == 0) {
                    icon.removeClass("bxs-star");
                    icon.addClass("bx-star");
                    icon.attr(temp, add_favorite); // Update the tooltip text
                    toastr.success(
                        label_project_removed_from_favorite_successfully
                    );
                } else {
                    icon.removeClass("bx-star");
                    icon.addClass("bxs-star");
                    icon.attr(temp, remove_favorite); // Update the tooltip text
                    toastr.success(
                        label_project_marked_as_favorite_successfully
                    );
                }
            }
        },
        error: function (data) {
            // Handle errors if necessary
            toastr.error(error);
        },
    });
});
$(document).on("click", ".duplicate", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var type = $(this).data("type");
    var reload = $(this).data("reload"); // Get the value of data-reload attribute
    if (typeof reload !== "undefined" && reload === true) {
        reload = true;
    } else {
        reload = false;
    }
    var tableID = $(this).data("table") || "table";
    $("#duplicateModal").modal("show"); // show the confirmation modal
    $("#duplicateModal").off("click", "#confirmDuplicate");
    if (type != "estimates-invoices" && type != "payslips") {
        $("#duplicateModal").find("#titleDiv").removeClass("d-none");
        var title = $(this).data("title");
        $("#duplicateModal").find("#updateTitle").val(title);
    } else {
        $("#duplicateModal").find("#titleDiv").addClass("d-none");
    }
    $("#duplicateModal").on("click", "#confirmDuplicate", function (e) {
        e.preventDefault();
        var title = $("#duplicateModal").find("#updateTitle").val();
        $("#confirmDuplicate").html(label_please_wait).attr("disabled", true);
        $.ajax({
            url:
                "/master-panel/" +
                type +
                "/duplicate/" +
                id +
                "?reload=" +
                reload +
                "&title=" +
                title,
            type: "GET",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                $("#confirmDuplicate").html(label_yes).attr("disabled", false);
                $("#duplicateModal").modal("hide");
                if (response.error == false) {
                    if (reload) {
                        location.reload();
                    } else {
                        toastr.success(response.message);
                        if (tableID) {
                            $("#" + tableID).bootstrapTable("refresh");
                        }
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (data) {
                $("#confirmDuplicate").html(label_yes).attr("disabled", false);
                $("#duplicateModal").modal("hide");
                var response = data.responseJSON;
                if (response.error) {
                    toastr.error(response.message);
                } else {
                    toastr.error(label_something_went_wrong);
                }
            },
        });
    });
});
$("#deduction_type").on("change", function (e) {
    if ($("#deduction_type").val() == "amount") {
        $("#amount_div").removeClass("d-none");
        $("#percentage_div").addClass("d-none");
    } else if ($("#deduction_type").val() == "percentage") {
        $("#amount_div").addClass("d-none");
        $("#percentage_div").removeClass("d-none");
    } else {
        $("#amount_div").addClass("d-none");
        $("#percentage_div").addClass("d-none");
    }
});
$("#update_deduction_type").on("change", function (e) {
    if ($("#update_deduction_type").val() == "amount") {
        $("#update_amount_div").removeClass("d-none");
        $("#update_percentage_div").addClass("d-none");
    } else if ($("#update_deduction_type").val() == "percentage") {
        $("#update_amount_div").addClass("d-none");
        $("#update_percentage_div").removeClass("d-none");
    } else {
        $("#update_amount_div").addClass("d-none");
        $("#update_percentage_div").addClass("d-none");
    }
});
if (document.getElementById("system-update-dropzone")) {
    var is_error = false;
    if (!$("#system-update").hasClass("dropzone")) {
        var systemDropzone = new Dropzone("#system-update-dropzone", {
            url: $("#system-update").attr("action"),
            paramName: "update_file",
            autoProcessQueue: false,
            parallelUploads: 1,
            maxFiles: 1,
            acceptedFiles: ".zip",
            timeout: 360000,
            autoDiscover: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // Pass the CSRF token as a header
            },
            addRemoveLinks: true,
            dictRemoveFile: "x",
            dictMaxFilesExceeded: "Only 1 file can be uploaded at a time",
            dictResponseError: "Error",
            uploadMultiple: true,
            dictDefaultMessage:
                '<p><input type="button" value="Select Files" class="btn btn-primary" /><br> or <br> Drag & Drop System Update / Installable / Plugin\'s .zip file Here</p>',
        });
        systemDropzone.on("addedfile", function (file) {
            var i = 0;
            if (this.files.length) {
                var _i, _len;
                for (_i = 0, _len = this.files.length; _i < _len - 1; _i++) {
                    if (
                        this.files[_i].name === file.name &&
                        this.files[_i].size === file.size &&
                        this.files[_i].lastModifiedDate.toString() ===
                        file.lastModifiedDate.toString()
                    ) {
                        this.removeFile(file);
                        i++;
                    }
                }
            }
        });
        systemDropzone.on("error", function (file, response) { });
        systemDropzone.on("sending", function (file, xhr, formData) {
            formData.append("flash_message", 1);
            xhr.onreadystatechange = function (response) {
                // return;
                setTimeout(function () {
                    location.reload();
                }, 2000);
            };
        });
        $("#system_update_btn").on("click", function (e) {
            e.preventDefault();
            if (is_error == false) {
                if (systemDropzone.files.length === 0) {
                    // Show toast message if no file is selected
                    toastr.error("Please select a file to upload.");
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                }
                $("#system_update_btn")
                    .attr("disabled", true)
                    .text(label_please_wait);
                systemDropzone.processQueue();
            }
        });
    }
}
if (document.getElementById("media-upload-dropzone")) {
    var is_error = false;
    var mediaDropzone = new Dropzone("#media-upload-dropzone", {
        url: $("#media-upload").attr("action"),
        paramName: "media_files",
        autoProcessQueue: false,
        timeout: 360000,
        autoDiscover: false,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // Pass the CSRF token as a header
        },
        addRemoveLinks: true,
        dictRemoveFile: "x",
        dictResponseError: "Error",
        uploadMultiple: true,
        dictDefaultMessage:
            '<p><input type="button" value="Select" class="btn btn-primary" /><br> or <br> Drag & Drop Files Here</p>',
    });
    mediaDropzone.on("addedfile", function (file) {
        var i = 0;
        if (this.files.length) {
            var _i, _len;
            for (_i = 0, _len = this.files.length; _i < _len - 1; _i++) {
                if (
                    this.files[_i].name === file.name &&
                    this.files[_i].size === file.size &&
                    this.files[_i].lastModifiedDate.toString() ===
                    file.lastModifiedDate.toString()
                ) {
                    this.removeFile(file);
                    i++;
                }
            }
        }
    });
    mediaDropzone.on("error", function (file, response) {
        return;
    });
    mediaDropzone.on("sending", function (file, xhr, formData) {
        var id = $("#media_type_id").val();
        formData.append("flash_message", 1);
        formData.append("id", id);
        xhr.onreadystatechange = function (response) {
            setTimeout(function () {
                location.reload();
            }, 2000);
        };
    });
    $("#upload_media_btn").on("click", function (e) {
        e.preventDefault();
        if (mediaDropzone.getQueuedFiles().length > 0) {
            if (is_error == false) {
                $("#upload_media_btn")
                    .attr("disabled", true)
                    .text(label_please_wait);
                mediaDropzone.processQueue();
                return;
            }
        } else {
            toastr.error("No file(s) chosen.");
        }
    });
}
// Row-wise Select/Deselect All
$(".row-permission-checkbox").change(function () {
    var module = $(this).data("module");
    var isChecked = $(this).prop("checked");
    $(`.permission-checkbox[data-module="${module}"]`).prop(
        "checked",
        isChecked
    );
});
$("#selectAllColumnPermissions").change(function () {
    var isChecked = $(this).prop("checked");
    $(".permission-checkbox").prop("checked", isChecked);
    if (isChecked) {
        $(".row-permission-checkbox").prop("checked", true).trigger("change"); // Check all row permissions when select all is checked
    } else {
        $(".row-permission-checkbox").prop("checked", false).trigger("change"); // Uncheck all row permissions when select all is unchecked
    }
    checkAllPermissions(); // Check all permissions
});
// Select/Deselect All for Rows
$("#selectAllPermissions").change(function () {
    var isChecked = $(this).prop("checked");
    $(".row-permission-checkbox").prop("checked", isChecked).trigger("change");
});
// Function to check/uncheck all permissions for a module
function checkModulePermissions(module) {
    var allChecked = true;
    $('.permission-checkbox[data-module="' + module + '"]').each(function () {
        if (!$(this).prop("checked")) {
            allChecked = false;
        }
    });
    $("#selectRow" + module).prop("checked", allChecked);
}
// Function to check if all permissions are checked and select/deselect "Select all" checkbox
function checkAllPermissions() {
    var allPermissionsChecked = true;
    $(".permission-checkbox").each(function () {
        if (!$(this).prop("checked")) {
            allPermissionsChecked = false;
        }
    });
    $("#selectAllColumnPermissions").prop("checked", allPermissionsChecked);
}
// Event handler for individual permission checkboxes
$(".permission-checkbox").on("change", function () {
    var module = $(this).data("module");
    checkModulePermissions(module);
    checkAllPermissions();
});
// Event handler for "Select all" checkbox
$("#selectAllColumnPermissions").on("change", function () {
    var isChecked = $(this).prop("checked");
    $(".permission-checkbox").prop("checked", isChecked);
});
// Initial check for permissions on page load
$(".row-permission-checkbox").each(function () {
    var module = $(this).data("module");
    checkModulePermissions(module);
});
checkAllPermissions();
$(document).ready(function () {
    $(".fixed-table-toolbar").each(function () {
        var $toolbar = $(this);
        var $data_type = $toolbar
            .closest(".table-responsive")
            .find("#data_type");
        var $data_table = $toolbar
            .closest(".table-responsive")
            .find("#data_table");
        var $save_column_visibility = $toolbar
            .closest(".table-responsive")
            .find("#save_column_visibility");
        if ($data_type.length > 0) {
            var data_type = $data_type.val();
            var data_table = $data_table.val() || "table";
            // Create the "Delete selected" button
            var $deleteButton = $(
                '<div class="columns columns-left btn-group float-left action_delete_' +
                data_type.replace("-", "_") +
                '">' +
                '<button type="button" class="btn btn-outline-danger float-left delete-selected" data-type="' +
                data_type +
                '" data-table="' +
                data_table +
                '">' +
                '<i class="bx bx-trash"></i> ' +
                label_delete_selected +
                "</button>" +
                "</div>"
            );
            // Add the "Delete selected" button before the first element in the toolbar
            $toolbar.prepend($deleteButton);
            if (data_type == "tasks") {
                // Create the "Clear Filters" button
                var $clearFiltersButton = $(
                    '<div class="columns columns-left btn-group float-left">' +
                    '<button type="button" class="btn btn-outline-secondary clear-filters">' +
                    '<i class="bx bx-x-circle"></i> ' +
                    label_clear_filters +
                    "</button>" +
                    "</div>"
                );
                $deleteButton.after($clearFiltersButton);
            }
            if ($save_column_visibility.length > 0) {
                var $savePreferencesButton = $(
                    '<div class="columns columns-left btn-group float-left">' +
                    '<button type="button" class="btn btn-outline-primary save-column-visibility" data-type="' +
                    data_type +
                    '" data-table="' +
                    data_table +
                    '">' +
                    '<i class="bx bx-save"></i> ' +
                    label_save_column_visibility +
                    "</button>" +
                    "</div>"
                );
                $deleteButton.after($savePreferencesButton);
            }
        }
    });
});
$("#media_storage_type").on("change", function (e) {
    if ($("#media_storage_type").val() == "s3") {
        $(".aws-s3-fields").removeClass("d-none");
    } else {
        $(".aws-s3-fields").addClass("d-none");
    }
});
$(document).on("click", ".edit-milestone", function () {
    var id = $(this).data("id");
    var urlPrefix = window.location.pathname.split("/")[1];
    $.ajax({
        url: "/" + urlPrefix + "/projects/get-milestone/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            var formattedStartDate = moment(response.ms.start_date).format(
                js_date_format
            );
            var formattedEndDate = moment(response.ms.end_date).format(
                js_date_format
            );
            $("#milestone_id").val(response.ms.id);
            $("#milestone_title").val(response.ms.title);
            $("#update_milestone_start_date").val(formattedStartDate);
            $("#update_milestone_end_date").val(formattedEndDate);
            $("#milestone_status").val(response.ms.status);
            $("#milestone_cost").val(response.ms.cost);
            $("#milestone_description").val(response.ms.description);
            $("#milestone_progress").val(response.ms.progress);
            $(".milestone-progress").text(response.ms.progress + "%");
        },
    });
});
// subscriptions start and end date
$(document).ready(function () {
    if (
        window.location.href.includes("transactions") ||
        window.location.href.includes("plans") ||
        window.location.href.includes("customers")
    ) {
        var deleteBtn = $(".delete-selected");
        // Hide the delete button
        deleteBtn.addClass("d-none");
    }
});
$(document).on("click", ".edit-expense-type", function () {
    var id = $(this).data("id");
    $("#edit_expense_type_modal").modal("show");
    var urlPrefix = window.location.pathname.split("/")[1];
    $.ajax({
        url: "/" + urlPrefix + "/expenses/get-expense-type/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#update_expense_type_id").val(response.et.id);
            $("#expense_type_title").val(response.et.title);
            $("#expense_type_description").val(response.et.description);
        },
    });
});
$(document).on("click", ".edit-expense", function () {
    var id = $(this).data("id");
    $("#edit_expense_modal").modal("show");
    var urlPrefix = window.location.pathname.split("/")[1];
    $.ajax({
        url: "/" + urlPrefix + "/expenses/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            var formattedExpDate = moment(response.exp.expense_date).format(
                js_date_format
            );
            var amount = parseFloat(response.exp.amount);
            $("#update_expense_id").val(response.exp.id);
            $("#expense_title").val(response.exp.title);
            $("#expense_type_id").val(response.exp.expense_type_id);
            $("#expense_user_id").val(response.exp.user_id);
            $("#expense_amount").val(amount.toFixed(decimal_points));
            $("#update_expense_date").val(formattedExpDate);
            $("#expense_note").val(response.exp.note);
        },
    });
});
$(document).on("click", ".edit-payment", function () {
    var id = $(this).data("id");
    $("#edit_payment_modal").modal("show");
    var urlPrefix = window.location.pathname.split("/")[1];
    $.ajax({
        url: "/" + urlPrefix + "/payments/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            var formattedExpDate = moment(response.payment.payment_date).format(
                js_date_format
            );
            var amount = parseFloat(response.payment.amount);
            $("#update_payment_id").val(response.payment.id);
            $("#payment_invoice_id").val(response.payment.invoice_id);
            $("#payment_amount").val(amount.toFixed(decimal_points));
            $("#update_payment_date").val(formattedExpDate);
            $("#payment_note").val(response.payment.note);
            var userId = response.payment.user.id;
            var userName =
                response.payment.user.first_name +
                " " +
                response.payment.user.last_name;
            if (
                $("#payment_user_id").find("option[value='" + userId + "']")
                    .length
            ) {
                $("#payment_user_id").val(userId).trigger("change");
            } else {
                var newOption = new Option(userName, userId, true, true);
                $("#payment_user_id").append(newOption).trigger("change");
            }
            var pm_id = response.payment.payment_method.id;
            var pm_title = response.payment.payment_method.title;
            if (
                $("#payment_pm_id").find("option[value='" + pm_id + "']").length
            ) {
                $("#payment_pm_id").val(pm_id).trigger("change");
            } else {
                var newOption = new Option(pm_title, pm_id, true, true);
                $("#payment_pm_id").append(newOption).trigger("change");
            }
        },
    });
});
function initializeDateRangePicker(inputSelector) {
    var modalId = $(inputSelector).closest(".modal").attr("id");
    $(inputSelector).daterangepicker({
        alwaysShowCalendars: true,
        showCustomRangeLabel: true,
        minDate: moment($(inputSelector).val(), js_date_format),
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: true,
        parentEl: "#" + modalId,
        locale: {
            cancelLabel: "Clear",
            format: js_date_format,
        },
    });
}
$(document).ready(function () {
    $("#togglePassword").on("click", function () {
        var passwordInput = $("#password");
        var toggleButton = $(this);
        // Toggle password visibility
        if (passwordInput.attr("type") === "password") {
            passwordInput.attr("type", "text");
            toggleButton.html('<i class="far fa-eye"></i>');
        } else {
            passwordInput.attr("type", "password");
            toggleButton.html('<i class="far fa-eye-slash"></i>');
        }
    });
});
$(document).on("click", ".superadmin-login", function (e) {
    e.preventDefault();
    $("#email").val("superadmin@gmail.com");
    $("#password").val("12345678");
});
$(document).on("click", ".admin-login", function (e) {
    e.preventDefault();
    $("#email").val("admin@gmail.com");
    $("#password").val("12345678");
});
$(document).on("click", ".member-login", function (e) {
    e.preventDefault();
    $("#email").val("teammember@gmail.com");
    $("#password").val("12345678");
});
$(document).on("click", ".client-login", function (e) {
    e.preventDefault();
    $("#email").val("client@gmail.com");
    $("#password").val("12345678");
});
$("#show_password").on("click", function () {
    var eyeicon = $("#eyeicon");
    let password = document.getElementById("password");
    if (password.type == "password") {
        password.type = "text";
        eyeicon.removeClass("bx-hide");
        eyeicon.addClass("bx-show");
    } else {
        password.type = "password";
        eyeicon.removeClass("bx-show");
        eyeicon.addClass("bx-hide");
    }
});
$("#show_confirm_password").on("click", function () {
    var eyeicon = $("#eyeicon");
    let confirm_password = document.getElementById("password_confirmation");
    if (confirm_password.type == "password") {
        confirm_password.type = "text";
        eyeicon.removeClass("bx-hide");
        eyeicon.addClass("bx-show");
    } else {
        confirm_password.type = "password";
        eyeicon.removeClass("bx-show");
        eyeicon.addClass("bx-hide");
    }
});
$(".min_0").on("change", function () {
    var amount = $(this).val();
    if (amount < 0) {
        $(this).val("");
        toastr.error(label_min_0);
    } else {
        // Clear error message if the value is valid
    }
});
$(".max_100").on("change", function () {
    var percentage = $(this).val();
    if (percentage > 100) {
        toastr.error(lable_max_100);
    } else {
        // Clear error message if the value is valid
    }
});
function clearModalContents($modal) {
    // Clear all input fields
    $modal.find('input:not([type="hidden"])').each(function () {
        if (
            $(this).attr("type") === "checkbox" ||
            $(this).attr("type") === "radio"
        ) {
            $(this).prop("checked", false);
        } else {
            $(this).val("");
        }
    });
    // Clear all textarea fields
    $modal.find("textarea").val("");
    // Reset all select elements
    $modal.find("select").prop("selectedIndex", 0);
    // Clear any error messages or validation states
    $modal
        .find(".error-message")
        .removeClass("text-danger")
        .closest("p")
        .text("");
    // Reset Select2 elements
    $modal.find("select").each(function () {
        if ($(this).data("select2")) {
            $(this).val(null).trigger("change");
        }
    });
    // Reset the form inside the modal
    $modal.find("form").trigger("reset");
}
// Usage for all modals
$(document).on("hidden.bs.modal", ".modal", function () {
    // var $modal = $(this);
    // if ($modal.attr('id') !== 'timerModal') {
    //     clearModalContents($modal);
    // }
    var modalId = $(this).attr("id");
    var $form = $(this).find("form"); // Find the form inside the modal
    $form.trigger("reset"); // Reset the form
    $form.find(".error-message").html("");
    var partialLeaveCheckbox = $("#partialLeave");
    if (partialLeaveCheckbox.length) {
        partialLeaveCheckbox.trigger("change");
    }
    var leaveVisibleToAllCheckbox = $form.find(".leaveVisibleToAll");
    if (leaveVisibleToAllCheckbox.length) {
        leaveVisibleToAllCheckbox.trigger("change");
    }
    var defaultColor =
        modalId == "create_note_modal" || modalId == "edit_note_modal"
            ? "success"
            : "primary";
    var colorSelect = $form.find('select[name="color"]');
    if (colorSelect.length) {
        var classes = colorSelect.attr("class").split(" ");
        var currentColorClass = classes.filter(function (className) {
            return className.startsWith("select-");
        })[0];
    }
    colorSelect
        .removeClass(currentColorClass)
        .addClass("select-bg-label-" + defaultColor);
    $form.find(".js-example-basic-multiple").trigger("change");
    if ($('.selectTaskProject[name="project"]').length) {
        $form.find($('.selectTaskProject[name="project"]')).trigger("change");
    }
    if ($('.selectLruser[name="user_id"]').length) {
        $form.find($('.selectLruser[name="user_id"]')).trigger("change");
    }
    if ($("#users_associated_with_project").length) {
        $("#users_associated_with_project").text("");
    }
    if ($("#task_update_users_associated_with_project").length) {
        $("#task_update_users_associated_with_project").text("");
    }
    $form.find("#reminder-settings").addClass("d-none");
    $form.find("#edit-reminder-settings").addClass("d-none");
    $form.find("#reminder-settings").find("input").prop("checked", false);
    $form.find("#edit-reminder-settings").find("input").prop("checked", false);
    $form.find("#recurring-task-settings").addClass("d-none");
    $form.find("#edit-recurring-task-settings").addClass("d-none");
    $form.find("#recurring-task-settings").find("input").prop("checked", false);
    $form.find("#edit-recurring-task-settings").find("input").prop("checked", false);
    resetDateFields($form);
});
$(document).on("click", "#mark-all-notifications-as-read", function (e) {
    e.preventDefault();
    $("#mark_all_notifications_as_read_modal").modal("show"); // show the confirmation modal
    $("#mark_all_notifications_as_read_modal").on(
        "click",
        "#confirmMarkAllAsRead",
        function () {
            $("#confirmMarkAllAsRead")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                url: "/master-panel/notifications/mark-all-as-read",
                type: "PUT",
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
                },
                success: function (response) {
                    location.reload();
                    // $('#confirmMarkAllAsRead').html(label_yes).attr('disabled', false);
                },
            });
        }
    );
});
$(document).on("click", ".update-notification-status", function (e) {
    var notificationId = $(this).data("id");
    var needConfirm = $(this).data("needconfirm") || false;
    if (needConfirm) {
        // Show the confirmation modal
        $("#update_notification_status_modal").modal("show");
        // Attach click event handler to the confirmation button
        $("#update_notification_status_modal").off(
            "click",
            "#confirmNotificationStatus"
        );
        $("#update_notification_status_modal").on(
            "click",
            "#confirmNotificationStatus",
            function () {
                $("#confirmNotificationStatus")
                    .html(label_please_wait)
                    .attr("disabled", true);
                performUpdate(notificationId, needConfirm);
            }
        );
    } else {
        // If confirmation is not needed, directly perform the update and handle response
        performUpdate(notificationId);
    }
});
function performUpdate(notificationId, needConfirm = "") {
    $.ajax({
        url: "/master-panel/notifications/update-status",
        type: "PUT",
        data: { id: notificationId, needConfirm: needConfirm },
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        success: function (response) {
            if (needConfirm) {
                $("#confirmNotificationStatus")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    toastr.success(response.message);
                    $("#table").bootstrapTable("refresh");
                } else {
                    toastr.error(response.message);
                }
                $("#update_notification_status_modal").modal("hide");
            }
        },
    });
}
if (
    typeof manage_notifications !== "undefined" &&
    manage_notifications == "true"
) {
    function updateUnreadNotifications() {
        // Make an AJAX request to fetch the count and HTML of unread notifications
        $.ajax({
            url: "/master-panel/notifications/get-unread-notifications",
            type: "GET",
            dataType: "json",
            success: function (data) {
                const unreadNotificationsCount = data.count;
                const unreadNotificationsHtml = data.html;
                // Update the count in the badge
                $("#unreadNotificationsCount").text(unreadNotificationsCount);
                // if (unreadNotificationsCount == 0) {
                //     $('#mark-all-notifications-as-read').addClass('disabled');
                // } else {
                //     $('#mark-all-notifications-as-read').removeClass('disabled');
                // }
                // Update the notifications list with the new HTML
                $("#unreadNotificationsContainer").html(
                    unreadNotificationsHtml
                );
            },
            error: function (xhr, status, error) {
                console.error("Error fetching unread notifications:", error);
            },
        });
    }
    // Call the updateUnreadNotifications function initially
    updateUnreadNotifications();
    // Update the unread notifications every 30 seconds
    setInterval(updateUnreadNotifications, 30000);
}
$(
    "textarea#email_verify_email,textarea#email_account_creation,textarea#email_forgot_password,textarea#email_project_assignment,textarea#email_task_assignment,textarea#email_workspace_assignment,textarea#email_meeting_assignment,textarea#email_leave_request_creation,textarea#email_leave_request_status_updation,textarea#email_project_status_updation,textarea#email_task_status_updation,textarea#email_team_member_on_leave_alert,textarea#email_project_issue_assignment,textarea#email_announcement,textarea#email_task_reminder,textarea#email_recurring_task"
).tinymce({
    height: 821,
    menubar: true,
    plugins: [
        "link",
        "a11ychecker",
        "advlist",
        "advcode",
        "advtable",
        "autolink",
        "checklist",
        "export",
        "lists",
        "link",
        "image",
        "charmap",
        "preview",
        "anchor",
        "searchreplace",
        "visualblocks",
        "powerpaste",
        "fullscreen",
        "formatpainter",
        "insertdatetime",
        "media",
        "table",
        "help",
        "wordcount",
        "emoticons",
        "code",
    ],
    toolbar: false,
    // toolbar: 'link | undo redo | a11ycheck casechange blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | removeformat | code blockquote emoticons table help'
});
// Handle click event on toolbar items
$(".tox-tbtn").click(function () {
    // Get the current editor instance
    var editor = tinyMCE.activeEditor;
    // Close any open toolbar dropdowns
    tinymce.ui.Factory.each(function (ctrl) {
        if (ctrl.type === "toolbarbutton" && ctrl.settings.toolbar) {
            if (ctrl !== this && ctrl.settings.toolbar === "toolbox") {
                ctrl.panel.hide();
            }
        }
    }, editor);
    // Execute the action associated with the clicked toolbar item
    editor.execCommand("mceInsertContent", false, "Clicked!");
});
$(document).on("click", ".restore-default", function (e) {
    e.preventDefault();
    var form = $(this).closest("form");
    var type = form.find('input[name="type"]').val();
    var name = form.find('input[name="name"]').val();
    var textarea = type + "_" + name;
    $("#restore_default_modal").modal("show"); // show the confirmation modal
    $("#restore_default_modal").off("click", "#confirmRestoreDefault");
    $("#restore_default_modal").on(
        "click",
        "#confirmRestoreDefault",
        function () {
            $("#confirmRestoreDefault")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                url: "/superadmin/settings/get-default-template",
                type: "POST",
                data: { type: type, name: name },
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
                },
                dataType: "json",
                success: function (response) {
                    $("#confirmRestoreDefault")
                        .html(label_yes)
                        .attr("disabled", false);
                    $("#restore_default_modal").modal("hide");
                    if (response.error == false) {
                        tinymce.get(textarea).setContent(response.content);
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
            });
        }
    );
});
$(document).on("click", ".sms-restore-default", function (e) {
    e.preventDefault();
    var form = $(this).closest("form");
    var type = form.find('input[name="type"]').val();
    var name = form.find('input[name="name"]').val();
    var textarea = type + "_" + name;
    $("#restore_default_modal").modal("show"); // show the confirmation modal
    $("#restore_default_modal").off("click", "#confirmRestoreDefault");
    $("#restore_default_modal").on(
        "click",
        "#confirmRestoreDefault",
        function () {
            $("#confirmRestoreDefault")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                url: "/superadmin/settings/get-default-template",
                type: "POST",
                data: { type: type, name: name },
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
                },
                dataType: "json",
                success: function (response) {
                    $("#confirmRestoreDefault")
                        .html(label_yes)
                        .attr("disabled", false);
                    $("#restore_default_modal").modal("hide");
                    if (response.error == false) {
                        $("#" + textarea).val(response.content);
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
            });
        }
    );
});
$(document).on("click", ".edit-language", function () {
    var id = $(this).data("id");
    $("#edit_language_modal").modal("show");
    $.ajax({
        url: "/superadmin/settings/languages/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#language_id").val(response.language.id);
            $("#language_title").val(response.language.name);
        },
    });
});
$(document).on("click", ".edit-priority", function () {
    var id = $(this).data("id");
    $("#edit_priority_modal").modal("show");
    var classes = $("#priority_color").attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    $.ajax({
        url: "/master-panel/priority/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            $("#priority_id").val(response.priority.id);
            $("#priority_title").val(response.priority.title);
            $("#priority_color")
                .val(response.priority.color)
                .removeClass(currentColorClass)
                .addClass("select-bg-label-" + response.priority.color);
        },
    });
});
$(document).on("click", ".openCreateStatusModal", function (e) {
    e.preventDefault();
    $("#create_status_modal").modal("show");
});
$(document).on("click", ".openCreatePriorityModal", function (e) {
    e.preventDefault();
    $("#create_priority_modal").modal("show");
});
$(document).on("click", ".openCreateTagModal", function (e) {
    e.preventDefault();
    $("#create_tag_modal").modal("show");
});
$(document).ready(function () {
    function formatTag(tag) {
        if (!tag.id) {
            return tag.text;
        }
        var color = $(tag.element).data("color");
        var $tag = $(
            '<span class="badge bg-label-' + color + '">' + tag.text + "</span>"
        );
        return $tag;
    }
    function formatStatus(status) {
        if (!status.id) {
            return status.text;
        }
        var color = $(status.element).data("color");
        var $status = $(
            '<span class="badge bg-label-' +
            color +
            '">' +
            status.text +
            "</span>"
        );
        return $status;
    }
    function formatPriority(priority) {
        if (!priority.id) {
            return priority.text;
        }
        var color = $(priority.element).data("color");
        var $priority = $(
            '<span class="badge bg-label-' +
            color +
            '">' +
            priority.text +
            "</span>"
        );
        return $priority;
    }
    $(".tagsDropdown").select2({
        templateResult: formatTag,
        templateSelection: formatTag,
        escapeMarkup: function (markup) {
            return markup;
        },
    });
    $(".statusDropdown").each(function () {
        var $this = $(this);
        $this.select2({
            dropdownParent: $this.closest(".modal"),
            templateResult: formatStatus,
            templateSelection: formatStatus,
            escapeMarkup: function (markup) {
                return markup;
            },
        });
    });
    $(".priorityDropdown").each(function () {
        var $this = $(this);
        $this.select2({
            dropdownParent: $this.closest(".modal"),
            templateResult: formatPriority,
            templateSelection: formatPriority,
            allowClear: true,
            escapeMarkup: function (markup) {
                return markup;
            },
            language: {
                noResults: function () {
                    return label_no_results_found;
                },
                searching: function () {
                    return label_searching;
                },
            },
        });
        // Prevent dropdown from opening when clear button is clicked
        $this
            .on("select2:unselecting", function (e) {
                $(this).data("state", "unselecting");
            })
            .on("select2:open", function (e) {
                if ($(this).data("state") === "unselecting") {
                    $(this).removeData("state");
                    $this.select2("close"); // Close the dropdown immediately
                }
            });
    });
    $(".selectTaskProject").each(function () {
        var $this = $(this);
        $this.select2({
            dropdownParent: $this.closest(".modal"),
        });
    });
});
$(document).on("click", ".edit-project", function () {
    var id = $(this).data("id");
    $("#edit_project_modal").modal("show");
    $.ajax({
        url: "/master-panel/projects/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedStartDate = moment(response.project.start_date).format(
                js_date_format
            );
            var formattedEndDate = moment(response.project.end_date).format(
                js_date_format
            );
            var $modal = $("#edit_project_modal"); // Cache the modal element for better performance
            $("#project_id").val(response.project.id);
            $("#project_title").val(response.project.title);
            $modal
                .find("#project_status_id")
                .val(response.project.status_id)
                .trigger("change");
            $modal
                .find("#project_priority_id")
                .val(response.project.priority_id)
                .trigger("change");
            $("#project_budget").val(response.project.budget);
            $("#update_start_date").val(formattedStartDate);
            $("#update_end_date").val(formattedEndDate);
            initializeDateRangePicker("#update_start_date, #update_end_date");
            $("#task_accessiblity").val(response.project.task_accessiblity);
            $("#edit_tasks_time_entries").prop(
                "checked",
                Boolean(response.project.enable_tasks_time_entries)
            );
            $("#project_description").val(response.project.description);
            $("#projectNote").val(response.project.note);
            // Populate project users in the multi-select dropdown
            var usersSelect = $("#edit_project_modal").find(
                '.js-example-basic-multiple[name="user_id[]"]'
            );
            // Preselect project users if they exist
            var projectUsers = response.users.map((user) => user.id);
            usersSelect.val(projectUsers);
            usersSelect.trigger("change"); // Trigger change event to update select2
            var clientsSelect = $("#edit_project_modal").find(
                '.js-example-basic-multiple[name="client_id[]"]'
            );
            var projectClients = response.clients.map((client) => client.id);
            clientsSelect.val(projectClients);
            clientsSelect.trigger("change"); // Trigger change event to update select2
            var tagsSelect = $("#edit_project_modal").find(
                '[name="tag_ids[]"]'
            );
            var projectTags = response.tags.map((tag) => tag.id);
            // Select old tags
            tagsSelect.val(projectTags);
            // Trigger change event to update Select2
            tagsSelect.trigger("change.select2");
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
    });
});
$(document).on("click", "#set-default-view", function (e) {
    e.preventDefault();
    var type = $(this).data("type");
    var view = $(this).data("view");
    var url = "/master-panel/save-" + type + "-view-preference";
    $("#set_default_view_modal").modal("show");
    $("#set_default_view_modal").off("click", "#confirm");
    $("#set_default_view_modal").on("click", "#confirm", function () {
        $("#set_default_view_modal")
            .find("#confirm")
            .html(label_please_wait)
            .attr("disabled", true);
        $.ajax({
            url: url,
            type: "PUT",
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            data: {
                type: type,
                view: view,
            },
            success: function (response) {
                $("#set_default_view_modal")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    $("#set-default-view")
                        .text(label_default_view)
                        .removeClass("bg-secondary")
                        .addClass("bg-primary");
                    $("#set_default_view_modal").modal("hide");
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
        });
    });
});
//task project select
$(document).ready(function () {
    $('.selectTaskProject[name="project"]').on("change", function (e) {
        var projectId = $(this).val();
        if (projectId) {
            $.ajax({
                url: "/master-panel/projects/get/" + projectId,
                type: "GET",
                success: function (response) {
                    $("#users_associated_with_project").html(
                        "(" +
                        label_users_associated_with_project +
                        " <strong>" +
                        response.project.title +
                        "</strong>)"
                    );
                    var usersSelect = $(
                        '.js-example-basic-multiple[name="users_id[]"]'
                    );
                    usersSelect.empty(); // Clear existing options
                    // Check if task_accessibility is 'project_users'
                    $.each(response.users, function (index, user) {
                        var option = $("<option>", {
                            value: user.id,
                            text: user.first_name + " " + user.last_name,
                        });
                        usersSelect.append(option);
                    });
                    if (
                        response.project.task_accessibility == "project_users"
                    ) {
                        var taskUsers = response.users.map((user) => user.id);
                        usersSelect.val(taskUsers);
                    } else {
                        usersSelect.val(authUserId);
                    }
                    usersSelect.trigger("change");

                    var taskLists = $('#task_list');
                    taskLists.empty(); // Clear existing options
                    // Check if task_accessibility is 'project_users'
                    $.each(response.task_lists, function (index, taskList) {
                        var option = $("<option>", {
                            value: taskList.id,
                            text: taskList.name,
                        });
                        taskLists.append(option);
                    });
                    if (response.task_lists.length == 0) {
                        var option = $("<option>", {
                            value: '',
                            text: label_no_task_list,
                        });
                        taskLists.append(option);
                    }


                },
                error: function (xhr, status, error) {
                    console.error(error);
                },
            });
        }
    });
});
$(document).on("click", ".quick-view", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var type = $(this).data("type") || "task";
    $("#type").val(type);
    $("#typeId").val(id);
    $.ajax({
        url: "/master-panel/" + type + "s/get/" + id,
        type: "GET",
        success: function (response) {
            if (response.error == false) {
                $("#quickViewModal").modal("show");
                if (type == "task" && response.task) {
                    $("#quickViewTitlePlaceholder").text(response.task.title);
                    $("#quickViewDescPlaceholder").html(
                        response.task.description
                    );
                } else if (type == "project" && response.project) {
                    $("#quickViewTitlePlaceholder").text(
                        response.project.title
                    );
                    $("#quickViewDescPlaceholder").html(
                        response.project.description
                    );
                }
                $("#typePlaceholder").text(
                    type == "task" ? label_task : label_project
                );
                $("#usersTable").bootstrapTable("refresh");
                $("#clientsTable").bootstrapTable("refresh");
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr, status, error) {
            // Handle error
            toastr.error("Something Went Wrong");
        },
    });
});
//edit task modal
$(document).on("click", ".edit-task", function () {
    var id = $(this).data("id");
    $("#edit_task_modal").modal("show");
    $.ajax({
        url: "/master-panel/tasks/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            console.log(response);
            var formattedStartDate = moment(response.task.start_date).format(
                js_date_format
            );
            var formattedEndDate = moment(response.task.end_date).format(
                js_date_format
            );
            $("#task_update_users_associated_with_project").html(
                "(" +
                label_users_associated_with_project +
                " <strong>" +
                response.project.title +
                "</strong>)"
            );
            $("#id").val(response.task.id);
            $("#title").val(response.task.title);
            $("#task_status_id").val(response.task.status_id).trigger("change");
            $("#priority_id").val(response.task.priority_id).trigger("change");
            $("#update_start_date").val(formattedStartDate);
            $("#update_end_date").val(formattedEndDate);
            initializeDateRangePicker("#update_start_date, #update_end_date");
            $("#update_project_title").val(response.project.title);
            $("#task_description").val(response.task.description);
            $("#taskNote").val(response.task.note);
            $("#edit_billing_type")
                .val(response.task.billing_type)
                .trigger("change");
            $("#edit_completion_percentage")
                .val(response.task.completion_percentage)
                .trigger("change");
            // Populate project users in the multi-select dropdown
            var usersSelect = $("#edit_task_modal").find(
                '.js-example-basic-multiple[name="user_id[]"]'
            );
            usersSelect.empty(); // Clear existing options
            $.each(response.project.users, function (index, user) {
                var option = $("<option>", {
                    value: user.id,
                    text: user.first_name + " " + user.last_name,
                });
                usersSelect.append(option);
            });
            // Preselect task users if they exist
            var taskUsers = response.task.users.map((user) => user.id);
            usersSelect.val(taskUsers);
            usersSelect.trigger("change"); // Trigger change event to update select2

            // Populate reminders
            if (response.task.reminders && response.task.reminders.length > 0) {
                const reminder = response.task.reminders[0];
                console.log(reminder);

                // Set reminder switch
                $('#edit-reminder-switch').prop('checked', reminder.is_active === 1);

                // Show reminder settings if active
                if (reminder.is_active === 1) {
                    $('#edit-reminder-settings').removeClass('d-none');
                } else {
                    $('#edit-reminder-settings').addClass('d-none');
                }

                // Set frequency type
                $('#edit-frequency-type').val(reminder.frequency_type).trigger('change');

                // Handle visibility and values for day selectors based on frequency type
                switch (reminder.frequency_type) {
                    case 'weekly':
                        $('#edit-day-of-week-group').removeClass('d-none');
                        $('#edit-day-of-month-group').addClass('d-none');
                        $('#edit-day-of-week').val(reminder.day_of_week || '');
                        break;
                    case 'monthly':
                        $('#edit-day-of-month-group').removeClass('d-none');
                        $('#edit-day-of-week-group').addClass('d-none');
                        $('#edit-day-of-month').val(reminder.day_of_month || '');
                        break;
                    default: // daily
                        $('#edit-day-of-week-group').addClass('d-none');
                        $('#edit-day-of-month-group').addClass('d-none');
                }

                // Set time of day
                // Convert the time string to correct format for input type="time"
                const timeOfDay = reminder.time_of_day.slice(0, 5); // Get HH:mm from HH:mm:ss
                $('#edit-time-of-day').val(timeOfDay);
            }

            // Populate recurring task settings
            if (response.task.recurring_task) {
                const recurring_task = response.task.recurring_task;


                // Set recurring task switch
                $('#edit-recurring-task-switch').prop('checked', recurring_task.is_active === 1);

                // Show recurring task settings if active
                if (recurring_task.is_active === 1) {
                    $('#edit-recurring-task-settings').removeClass('d-none');
                } else {
                    $('#edit-recurring-task-settings').addClass('d-none');
                }

                // Set frequency type
                $('#edit-recurrence-frequency').val(recurring_task.frequency).trigger('change');

                // Handle visibility and values for day selectors based on frequency type
                switch (recurring_task.frequency) {
                    case 'weekly':
                        $('#edit-recurrence-day-of-week-group').removeClass('d-none');
                        $('#edit-recurrence-day-of-month-group').addClass('d-none');
                        $('#edit-recurrence-day-of-week').val(recurring_task.day_of_week || '');
                        break;
                    case 'monthly':
                        $('#edit-recurrence-day-of-month-group').removeClass('d-none');
                        $('#edit-recurrence-day-of-week-group').addClass('d-none');
                        $('#edit-recurrence-day-of-month').val(recurring_task.day_of_month || '');
                        break;
                    case 'yearly':
                        $('#edit-recurrence-day-of-month-group').removeClass('d-none');
                        $('#edit-recurrence-day-of-week-group').addClass('d-none');
                        $('#edit-recurrence-day-of-month').val(recurring_task.day_of_month || '');
                        $('#edit-recurrence-month-of-year-group').removeClass('d-none');
                        $('#edit-recurrence-month-of-year').val(recurring_task.month_of_year || '');
                        break;
                    default: // daily
                        $('#edit-recurrence-day-of-week-group').addClass('d-none');
                        $('#edit-recurrence-day-of-month-group').addClass('d-none');
                        $('#edit-recurrence-month-of-year-group').addClass('d-none');
                }

                // Starts From and Number of Occuerences
                $('#edit-recurrence-starts-from').val(recurring_task.starts_from);
                $('#edit-recurrence-occurrences').val(recurring_task.number_of_occurrences);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
    });
});
// Column Visibility
$(document).on("click", ".save-column-visibility", function (e) {
    e.preventDefault();
    var tableName = $(this).data("table");
    var type = $(this).data("type");
    type = type.replace(/-/g, "_"); // This replaces all hyphens with underscores
    $("#confirmSaveColumnVisibility").modal("show");
    $("#confirmSaveColumnVisibility").off("click", "#confirm");
    $("#confirmSaveColumnVisibility").on("click", "#confirm", function () {
        $("#confirmSaveColumnVisibility")
            .find("#confirm")
            .html(label_please_wait)
            .attr("disabled", true);
        var visibleColumns = [];
        $("#" + tableName)
            .bootstrapTable("getVisibleColumns")
            .forEach((column) => {
                if (!column.checkbox) {
                    visibleColumns.push(column.field);
                }
            });
        // Send preferences to the server
        $.ajax({
            url: "/master-panel/save-column-visibility",
            type: "POST",
            data: {
                type: type,
                visible_columns: JSON.stringify(visibleColumns),
            },
            headers: {
                "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
            },
            success: function (response) {
                $("#confirmSaveColumnVisibility")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    $("#confirmSaveColumnVisibility").modal("hide");
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (data) {
                $("#confirmSaveColumnVisibility")
                    .find("#confirm")
                    .html(label_yes)
                    .attr("disabled", false);
                $("#confirmSaveColumnVisibility").modal("hide");
                toastr.error(label_something_went_wrong);
            },
        });
    });
});
// Edit Workspace Modal
$(document).on("click", ".edit-workspace", function () {
    var id = $(this).data("id");
    $("#editWorkspaceModal").modal("show");
    $.ajax({
        url: "/master-panel/workspaces/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            $("#workspace_id").val(response.workspace.id);
            $("#workspace_title").val(response.workspace.title);
            var usersSelect = $("#editWorkspaceModal").find(
                '.js-example-basic-multiple[name="user_ids[]"]'
            );
            var workspaceUsers = response.workspace.users.map(
                (user) => user.id
            );
            usersSelect.val(workspaceUsers);
            usersSelect.trigger("change"); // Trigger change event to update select2
            var clientsSelect = $("#editWorkspaceModal").find(
                '.js-example-basic-multiple[name="client_ids[]"]'
            );
            var workspaceClients = response.workspace.clients.map(
                (client) => client.id
            );
            clientsSelect.val(workspaceClients);
            clientsSelect.trigger("change"); // Trigger change event to update select2
            if (response.workspace.is_primary == 1) {
                $("#editWorkspaceModal")
                    .find("#updatePrimaryWorkspace")
                    .prop("checked", true)
                    .prop("disabled", true);
            } else {
                $("#editWorkspaceModal")
                    .find("#updatePrimaryWorkspace")
                    .prop("checked", false)
                    .prop("disabled", false);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
    });
});
// Edit Meetings
$(document).on("click", ".edit-meeting", function () {
    var id = $(this).data("id");
    $("#editMeetingModal").modal("show");
    $.ajax({
        url: "/master-panel/meetings/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        dataType: "json",
        success: function (response) {
            var formattedStartDate = moment(response.meeting.start_date).format(
                js_date_format
            );
            var formattedEndDate = moment(response.meeting.end_date).format(
                js_date_format
            );
            var startDateInput = $("#editMeetingModal").find(
                '[name="start_date"]'
            );
            var endDateInput = $("#editMeetingModal").find('[name="end_date"]');
            $("#meeting_id").val(response.meeting.id);
            $("#meeting_title").val(response.meeting.title);
            startDateInput.val(formattedStartDate);
            endDateInput.val(formattedEndDate);
            $("#meeting_start_time").val(response.meeting.start_time);
            $("#meeting_end_time").val(response.meeting.end_time);
            var usersSelect = $("#editMeetingModal").find(
                '.js-example-basic-multiple[name="user_ids[]"]'
            );
            var meetingUsers = response.meeting.users.map((user) => user.id);
            usersSelect.val(meetingUsers);
            usersSelect.trigger("change"); // Trigger change event to update select2
            var clientsSelect = $("#editMeetingModal").find(
                '.js-example-basic-multiple[name="client_ids[]"]'
            );
            var meetingClients = response.meeting.clients.map(
                (client) => client.id
            );
            clientsSelect.val(meetingClients);
            clientsSelect.trigger("change"); // Trigger change event to update select2
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
    });
});
$("#partialLeave, #updatePartialLeave").on("change", function () {
    var $form = $(this).closest("form"); // Get the closest form element
    var isChecked = $(this).prop("checked");
    if (isChecked) {
        // If the checkbox is checked
        $form
            .find(".leave-from-date-div")
            .removeClass("col-5")
            .addClass("col-3");
        $form.find(".leave-to-date-div").removeClass("col-5").addClass("col-3");
        $form
            .find(".leave-from-time-div, .leave-to-time-div")
            .removeClass("d-none");
        $form.find("#guide_text").removeClass("d-none");
    } else {
        // If the checkbox is unchecked, revert the changes
        $form.find('input[name="from_time"]').val("");
        $form.find('input[name="to_time"]').val("");
        $form
            .find(".leave-from-date-div")
            .removeClass("col-3")
            .addClass("col-5");
        $form.find(".leave-to-date-div").removeClass("col-3").addClass("col-5");
        $form
            .find(".leave-from-time-div, .leave-to-time-div")
            .addClass("d-none");
        $form.find("#guide_text").addClass("d-none");
    }
});
$(".leaveVisibleToAll").on("change", function () {
    var $form = $(this).closest("form"); // Get the closest form element
    var isChecked = $(this).prop("checked");
    if (isChecked) {
        // If the checkbox is checked
        $form.find(".leaveVisibleToDiv").addClass("d-none");
        var visibleToSelect = $form.find(
            '.js-example-basic-multiple[name="visible_to_ids[]"]'
        );
        visibleToSelect.val(null).trigger("change");
    } else {
        // If the checkbox is unchecked, revert the changes
        $form.find(".leaveVisibleToDiv").removeClass("d-none");
    }
});
$(document).ready(function () {
    var upcomingBDCalendarInitialized = false;
    var upcomingWACalendarInitialized = false;
    var membersOnLeaveCalendarInitialized = false;
    // Add event listener for tab shown event
    $(".nav-tabs .nav-item").on("shown.bs.tab", function (event) {
        var tabId = $(event.target).attr("data-bs-target");
        if (
            tabId == "#navs-top-upcoming-birthdays-calendar" &&
            !upcomingBDCalendarInitialized
        ) {
            initializeUpcomingBDCalendar();
            upcomingBDCalendarInitialized = true;
        } else if (
            tabId == "#navs-top-upcoming-work-anniversaries-calendar" &&
            !upcomingWACalendarInitialized
        ) {
            initializeUpcomingWACalendar();
            upcomingWACalendarInitialized = true;
        } else if (
            tabId == "#navs-top-members-on-leave-calendar" &&
            !membersOnLeaveCalendarInitialized
        ) {
            initializeMembersOnLeaveCalendar();
            membersOnLeaveCalendarInitialized = true;
        }
    });
});
function initializeUpcomingBDCalendar() {
    var upcomingBDCalendar = document.getElementById(
        "upcomingBirthdaysCalendar"
    );
    // Check if the calendar element exists
    if (upcomingBDCalendar) {
        var BDcalendar = new FullCalendar.Calendar(upcomingBDCalendar, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear",
            },
            editable: true,
            events: function (fetchInfo, successCallback, failureCallback) {
                // Make AJAX request to fetch dynamic data
                $.ajax({
                    url: "/master-panel/home/upcoming-birthdays-calendar",
                    type: "GET",
                    data: {
                        startDate: fetchInfo.startStr,
                        endDate: fetchInfo.endStr,
                    },
                    success: function (response) {
                        // Parse and format dynamic data for FullCalendar
                        var events = response.map(function (event) {
                            return {
                                title: event.title,
                                start: event.start,
                                end: event.start,
                                backgroundColor: event.backgroundColor,
                                borderColor: event.borderColor,
                                textColor: event.textColor,
                                userId: event.userId,
                            };
                        });
                        // Invoke success callback with dynamic data
                        successCallback(events);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        // Invoke failure callback if there's an error
                        failureCallback(error);
                    },
                });
            },
            eventClick: function (info) {
                if (
                    info.event.extendedProps &&
                    info.event.extendedProps.userId
                ) {
                    var userId = info.event.extendedProps.userId;
                    var url = "/master-panel/users/profile/" + userId;
                    window.open(url, "_blank"); // Open in a new tab
                }
            },
        });
        BDcalendar.render();
    }
}
function initializeUpcomingWACalendar() {
    var upcomingWACalendar = document.getElementById(
        "upcomingWorkAnniversariesCalendar"
    );
    // Check if the calendar element exists
    if (upcomingWACalendar) {
        var WAcalendar = new FullCalendar.Calendar(upcomingWACalendar, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear",
            },
            editable: true,
            height: "auto",
            events: function (fetchInfo, successCallback, failureCallback) {
                // Make AJAX request to fetch dynamic data
                $.ajax({
                    url: "/master-panel/home/upcoming-work-anniversaries-calendar",
                    type: "GET",
                    data: {
                        startDate: fetchInfo.startStr,
                        endDate: fetchInfo.endStr,
                    },
                    success: function (response) {
                        // Parse and format dynamic data for FullCalendar
                        var events = response.map(function (event) {
                            return {
                                title: event.title,
                                start: event.start,
                                end: event.start,
                                backgroundColor: event.backgroundColor,
                                borderColor: event.borderColor,
                                textColor: event.textColor,
                                userId: event.userId,
                            };
                        });
                        // Invoke success callback with dynamic data
                        successCallback(events);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        // Invoke failure callback if there's an error
                        failureCallback(error);
                    },
                });
            },
            eventClick: function (info) {
                if (
                    info.event.extendedProps &&
                    info.event.extendedProps.userId
                ) {
                    var userId = info.event.extendedProps.userId;
                    var url = "/master-panel/users/profile/" + userId;
                    window.open(url, "_blank"); // Open in a new tab
                }
            },
        });
        WAcalendar.render();
    }
}
function initializeMembersOnLeaveCalendar() {
    var membersOnLeaveCalendar = document.getElementById(
        "membersOnLeaveCalendar"
    );
    // Check if the calendar element exists
    if (membersOnLeaveCalendar) {
        var MOLcalendar = new FullCalendar.Calendar(membersOnLeaveCalendar, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear",
            },
            editable: true,
            displayEventTime: true,
            events: function (fetchInfo, successCallback, failureCallback) {
                // Make AJAX request to fetch dynamic data
                $.ajax({
                    url: "/master-panel/home/members-on-leave-calendar",
                    type: "GET",
                    data: {
                        startDate: fetchInfo.startStr,
                        endDate: fetchInfo.endStr,
                    },
                    success: function (response) {
                        // Parse and format dynamic data for FullCalendar
                        var events = response.map(function (event) {
                            var eventData = {
                                title: event.title,
                                start: event.start,
                                end: moment(event.end)
                                    .add(1, "days")
                                    .format("YYYY-MM-DD"),
                                backgroundColor: event.backgroundColor,
                                borderColor: event.borderColor,
                                textColor: event.textColor,
                                userId: event.userId,
                            };
                            // Check if the event is partial and has start and end times
                            if (event.startTime && event.endTime) {
                                // Include start and end times directly in the event data
                                eventData.extendedProps = {
                                    startTime: event.startTime,
                                    endTime: event.endTime,
                                };
                            }
                            return eventData;
                        });
                        // Invoke success callback with dynamic data
                        successCallback(events);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        // Invoke failure callback if there's an error
                        failureCallback(error);
                    },
                });
            },
            eventClick: function (info) {
                if (
                    info.event.extendedProps &&
                    info.event.extendedProps.userId
                ) {
                    var userId = info.event.extendedProps.userId;
                    var url = "/master-panel/users/profile/" + userId;
                    window.open(url, "_blank"); // Open in a new tab
                }
            },
        });
        MOLcalendar.render();
    }
}
// View Assigned Projects and Tasks
$(document).on("click", ".viewAssigned", function (e) {
    e.preventDefault();
    var projectsUrl = "/master-panel/projects/listing";
    var tasksUrl = "/master-panel/tasks/list";
    var id = $(this).data("id");
    var type = $(this).data("type");
    var user = $(this).data("user");
    projectsUrl = projectsUrl + (id ? "/" + id : "");
    tasksUrl = tasksUrl + (id ? "/" + id : "");
    $("#viewAssignedModal").modal("show");
    var projectsTable = $("#viewAssignedModal").find("#projects_table");
    var tasksTable = $("#viewAssignedModal").find("#task_table");
    if (type === "tasks") {
        $('.nav-link[data-bs-target="#navs-top-view-assigned-tasks"]').tab(
            "show"
        );
        $(
            '.nav-link[data-bs-target="#navs-top-view-assigned-projects"]'
        ).removeClass("active");
        $("#navs-top-view-assigned-projects").removeClass("show active");
        $("#navs-top-view-assigned-tasks").addClass("show active");
    } else {
        $('.nav-link[data-bs-target="#navs-top-view-assigned-projects"]').tab(
            "show"
        );
        $(
            '.nav-link[data-bs-target="#navs-top-view-assigned-tasks"]'
        ).removeClass("active");
        $("#navs-top-view-assigned-tasks").removeClass("show active");
        $("#navs-top-view-assigned-projects").addClass("show active");
    }
    $("#userPlaceholder").text(user);
    $(projectsTable).bootstrapTable("refresh", {
        url: projectsUrl,
    });
    $(tasksTable).bootstrapTable("refresh", {
        url: tasksUrl,
    });
});
// Internal Client
$("#internal_client").change(function () {
    var isChecked = $(this).prop("checked");
    $("#password, #password_confirmation").val("");
    $("#passDiv, #confirmPassDiv, #statusDiv, #requireEvDiv").toggleClass(
        "d-none",
        isChecked
    );
    $("#client_deactive").prop("checked", true);
    $("#require_ev_" + (isChecked ? "no" : "yes")).prop("checked", true);
    $("#password").next(".error-message").remove();
    $("#password_confirmation").next(".error-message").remove();
});
$("#update_internal_client").change(function () {
    var isChecked = $(this).prop("checked");
    $("#password, #password_confirmation").val("");
    $("#passDiv, #confirmPassDiv, #statusDiv, #requireEvDiv").toggleClass(
        "d-none",
        isChecked
    );
    // Remove .error-message elements next to #password and #password_confirmation
    $("#password").next(".error-message").remove();
    $("#password_confirmation").next(".error-message").remove();
});
//Open Create Contract Type Modal
$(document).on("click", ".openCreateContractTypeModal", function (e) {
    e.preventDefault();
    $("#create_contract_type_modal").modal("show");
});
// reset date
function resetDateFields($form) {
    var currentDate = moment(new Date()).format(js_date_format); // Get current date
    $form.find("input").each(function () {
        var $this = $(this);
        if ($this.data("daterangepicker")) {
            // Destroy old instance
            $this.data("daterangepicker").remove();
            // Reinitialize with new value
            $this.val(currentDate).daterangepicker({
                alwaysShowCalendars: true,
                showCustomRangeLabel: true,
                // minDate: moment($(id).val(), js_date_format),
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                locale: {
                    cancelLabel: "Clear",
                    format: js_date_format,
                },
            });
        }
    });
}
// Change Select Color
$(document).on("change", 'select[name="color"]', function (e) {
    e.preventDefault();
    var select = $(this);
    var classes = $(this).attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    var selectedOption = $(this).find("option:selected");
    var selectedOptionClasses = selectedOption.attr("class").split(" ");
    var newColorClass = "select-" + selectedOptionClasses[1];
    select.removeClass(currentColorClass).addClass(newColorClass);
});
// Select All Preferences
$(document).ready(function () {
    if ($("#selectAllPreferences").length) {
        // Check initial state of checkboxes and update selectAllPreferences checkbox
        updateSelectAll();
        // Select/deselect all checkboxes when the selectAllPreferences checkbox is clicked
        $("#selectAllPreferences").click(function () {
            var isChecked = $(this).prop("checked");
            $('input[name="enabled_notifications[]"]:not(:disabled)').prop(
                "checked",
                isChecked
            );
        });
        // Update the selectAllPreferences checkbox state based on the checkboxes' status
        $('input[name="enabled_notifications[]"]').change(function () {
            updateSelectAll();
        });
        // Function to update selectAllPreferences checkbox based on checkboxes' status
        function updateSelectAll() {
            var allChecked =
                $('input[name="enabled_notifications[]"]:not(:disabled)')
                    .length ===
                $(
                    'input[name="enabled_notifications[]"]:not(:disabled):checked'
                ).length;
            $("#selectAllPreferences").prop("checked", allChecked);
        }
    }
});
function toggleChatIframe() {
    var iframeContainer = document.getElementById("chatIframeContainer");
    if (
        iframeContainer.style.display === "none" ||
        iframeContainer.style.display === ""
    ) {
        iframeContainer.style.display = "block";
    } else {
        iframeContainer.style.display = "none";
    }
}
$(document).on("change", "#statusSelect", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var statusId = this.value;
    var type = $(this).data("type") || "project";
    var reload = $(this).data("reload") || false;
    var select = $(this);
    var originalStatusId = $(this).data("original-status-id");
    var originalColorClass = $(this).data("original-color-class");
    var classes = $(this).attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    var selectedOption = $(this).find("option:selected");
    var selectedOptionClasses = selectedOption.attr("class").split(" ");
    var newColorClass = "select-" + selectedOptionClasses[1];
    select.removeClass(currentColorClass).addClass(newColorClass);
    $.ajax({
        url: "/master-panel/" + type + "s/get/" + id,
        type: "GET",
        success: function (response) {
            if (response.error == false) {
                $("#confirmUpdateStatusModal").modal("show"); // show the confirmation modal
                $("#confirmUpdateStatusModal").off(
                    "click",
                    "#confirmUpdateStatus"
                );
                if (type == "task" && response.task) {
                    $("#statusNote").val(response.task.note);
                    originalStatusId = response.task.status_id;
                } else if (type == "project" && response.project) {
                    $("#statusNote").val(response.project.note);
                    originalStatusId = response.project.status_id;
                }
                $("#confirmUpdateStatusModal").on(
                    "click",
                    "#confirmUpdateStatus",
                    function (e) {
                        $("#confirmUpdateStatus")
                            .html(label_please_wait)
                            .attr("disabled", true);
                        // Send AJAX request to update status
                        $.ajax({
                            type: "POST",
                            url: "/master-panel/update-" + type + "-status",
                            headers: {
                                "X-CSRF-TOKEN": $('input[name="_token"]').attr(
                                    "value"
                                ),
                            },
                            data: {
                                id: id,
                                statusId: statusId,
                                note: $("#statusNote").val(),
                            },
                            success: function (response) {
                                $("#confirmUpdateStatus")
                                    .html(label_yes)
                                    .attr("disabled", false);
                                if (response.error == false) {
                                    setTimeout(function () {
                                        if (reload) {
                                            window.location.reload(); // Reload the current page
                                        }
                                    }, 3000);
                                    $("#confirmUpdateStatusModal").modal(
                                        "hide"
                                    );
                                    var tableSelector =
                                        type == "project"
                                            ? "projects_table"
                                            : "task_table";
                                    var $table = $("#" + tableSelector);
                                    if ($table.length) {
                                        $table.bootstrapTable("refresh");
                                    }
                                    toastr.success(response.message);
                                } else {
                                    select
                                        .removeClass(newColorClass)
                                        .addClass(originalColorClass);
                                    select.val(originalStatusId);
                                    toastr.error(response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                $("#confirmUpdateStatus")
                                    .html(label_yes)
                                    .attr("disabled", false);
                                // Handle error
                                select
                                    .removeClass(newColorClass)
                                    .addClass(originalColorClass);
                                select.val(originalStatusId);
                                toastr.error("Something Went Wrong");
                            },
                        });
                    }
                );
            } else {
                $("#confirmUpdateStatus")
                    .html(label_yes)
                    .attr("disabled", false);
                select.val(originalStatusId);
                toastr.error(response.message);
            }
        },
        error: function (xhr, status, error) {
            // Handle error
            toastr.error("Something Went Wrong");
        },
    });
    // Handle modal close event
    $("#confirmUpdateStatusModal").off(
        "click",
        ".btn-close, #declineUpdateStatus"
    );
    $("#confirmUpdateStatusModal").on(
        "click",
        ".btn-close, #declineUpdateStatus",
        function (e) {
            // Set original status when modal is closed without confirmation
            select.val(originalStatusId);
            select.removeClass(newColorClass).addClass(originalColorClass);
        }
    );
});
$(document).on("change", "#prioritySelect", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var priorityId = this.value;
    var type = $(this).data("type") || "project";
    var reload = $(this).data("reload") || false;
    var select = $(this);
    var originalPriorityId = $(this).data("original-priority-id") || 0;
    var originalColorClass = $(this).data("original-color-class");
    var classes = $(this).attr("class").split(" ");
    var currentColorClass = classes.filter(function (className) {
        return className.startsWith("select-");
    })[0];
    var selectedOption = $(this).find("option:selected");
    var selectedOptionClasses = selectedOption.attr("class").split(" ");
    var newColorClass = "select-" + selectedOptionClasses[1];
    select.removeClass(currentColorClass).addClass(newColorClass);
    $("#confirmUpdatePriorityModal").modal("show"); // show the confirmation modal
    $("#confirmUpdatePriorityModal").off("click", "#confirmUpdatePriority");
    $("#confirmUpdatePriorityModal").on(
        "click",
        "#confirmUpdatePriority",
        function (e) {
            $("#confirmUpdatePriority")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                type: "POST",
                url: "/master-panel/update-" + type + "-priority",
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
                },
                data: {
                    id: id,
                    priorityId: priorityId,
                },
                success: function (response) {
                    $("#confirmUpdatePriority")
                        .html(label_yes)
                        .attr("disabled", false);
                    if (response.error == false) {
                        setTimeout(function () {
                            if (reload) {
                                window.location.reload(); // Reload the current page
                            }
                        }, 3000);
                        $("#confirmUpdatePriorityModal").modal("hide");
                        toastr.success(response.message);
                        var tableSelector =
                            type == "project" ? "projects_table" : "task_table";
                        var $table = $("#" + tableSelector);
                        if ($table.length) {
                            $table.bootstrapTable("refresh");
                        }
                    } else {
                        select
                            .removeClass(newColorClass)
                            .addClass(originalColorClass);
                        select.val(originalPriorityId);
                        toastr.error(response.message);
                    }
                },
                error: function (xhr, status, error) {
                    $("#confirmUpdatePriority")
                        .html(label_yes)
                        .attr("disabled", false);
                    // Handle error
                    select
                        .removeClass(newColorClass)
                        .addClass(originalColorClass);
                    select.val(originalPriorityId);
                    toastr.error("Something Went Wrong");
                },
            });
        }
    );
});
$(document).ready(function () {
    if ($("#total_days").length) {
        // Function to calculate and display the total days for create modal
        function calculateCreateTotalDays() {
            var start_date = moment($("#start_date").val(), js_date_format);
            var end_date = moment($("#lr_end_date").val(), js_date_format);
            if (start_date.isValid() && end_date.isValid()) {
                var total_days = end_date.diff(start_date, "days") + 1;
                $("#total_days").val(total_days);
            }
        }
        // Bind the event handlers to both date pickers in the create modal
        $("#start_date").on("apply.daterangepicker", function (ev, picker) {
            calculateCreateTotalDays();
        });
        $("#lr_end_date").on("apply.daterangepicker", function (ev, picker) {
            calculateCreateTotalDays();
        });
    }
    if ($("#update_total_days").length) {
        // Function to calculate and display the total days for update modal
        function calculateUpdateTotalDays() {
            var start_date = moment(
                $("#update_start_date").val(),
                js_date_format
            );
            var end_date = moment($("#update_end_date").val(), js_date_format);
            if (start_date.isValid() && end_date.isValid()) {
                var total_days = end_date.diff(start_date, "days") + 1;
                $("#update_total_days").val(total_days);
            }
        }
        // Bind the event handlers to both date pickers in the update modal
        $("#update_start_date").on(
            "apply.daterangepicker",
            function (ev, picker) {
                calculateUpdateTotalDays();
            }
        );
        $("#update_end_date").on(
            "apply.daterangepicker",
            function (ev, picker) {
                calculateUpdateTotalDays();
            }
        );
    }
});
/// Search Functionality for the Menu Bar
$(document).ready(function () {
    var $searchInput = $("#menu-search");
    var $clearButton = $("#clear-search");
    var menuItems = $(".menu-item");
    // Search input event handler
    $searchInput.on("input", function () {
        var searchQuery = $searchInput.val().toLowerCase();
        $clearButton.toggle(searchQuery.length > 0);
        menuItems.each(function () {
            const $menuItem = $(this);
            const $parentMenu = $menuItem.closest(".menu-sub");
            const $parentMenuItem = $parentMenu.closest(".menu-item");
            const itemText = $menuItem.text().toLowerCase();
            if (itemText.includes(searchQuery)) {
                $menuItem.show();
                // Show and open parent menu item
                if ($parentMenuItem.length) {
                    $parentMenuItem.show().addClass("open");
                }
                // If this item has a submenu
                const $subMenu = $menuItem.find("> .menu-sub");
                if ($subMenu.length) {
                    $menuItem.addClass("open");
                }
            } else {
                // Only hide if it doesn't have visible children
                if (!$menuItem.find(".menu-item:visible").length) {
                    $menuItem.hide();
                }
            }
        });
    });
    // Clear button click event handler
    $clearButton.on("click", function () {
        $searchInput.val("");
        $clearButton.hide();
        menuItems.find(".open").removeClass("open");
        menuItems.show(); // Show all menu items
    });
});
function calenderView() {
    var calendar = document.getElementById("taskCalenderDiv");
    // Check if the calendar element exists
    if (calendar) {
        var project_id = $("#project_id").val();
        var WAcalendar = new FullCalendar.Calendar(calendar, {
            plugins: ["interaction", "dayGrid", "list"],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listYear",
            },
            editable: false,
            height: "auto",
            selectable: true, // Enable date selection
            select: function (info) {
                // Handle date selection
                openCreateTaskModal({
                    startDate: info.start,
                    endDate: info.end,
                });
            },
            dateClick: function (info) {
                // Handle single date click
                openCreateTaskModal({
                    startDate: info.date,
                    endDate: info.date,
                });
            },
            events: function (fetchInfo, successCallback, failureCallback) {
                // Fetch tasks for the current month
                fetchTasks(
                    fetchInfo.start,
                    fetchInfo.end,
                    successCallback,
                    failureCallback,
                    project_id
                );
            },
            datesSet: function (info) {
                // Fetch tasks when the month changes
                WAcalendar.removeAllEvents();
                WAcalendar.refetchEvents();
            },
            eventClick: function (info) {
                // Redirect to the task info page
                window.open(info.event.extendedProps.tasks_info_url, "_blank");
            },
        });
        WAcalendar.render();
    }
}
// Helper function to open the create task modal
function openCreateTaskModal(dates) {
    // Format dates to your required format (example uses YYYY-MM-DD)
    const startDate = formatDate(dates.startDate);
    const endDate = formatDate(dates.endDate);
    // If you're using a bootstrap modal
    const modal = new bootstrap.Modal(
        document.getElementById("create_task_modal")
    );
    // Set the date values in your form inputs
    document.getElementById("task_start_date").value = startDate;
    document.getElementById("task_end_date").value = endDate;
    // Show the modal
    modal.show();
}
// Helper function to format dates according to system format
function formatDate(date) {
    if (!date) return "";
    try {
        // Create a moment object and format according to system date format
        return moment(date).format(js_date_format);
    } catch (error) {
        console.error("Error formatting date:", error);
        // Fallback to ISO format if there's an error
        return date.toISOString().split("T")[0];
    }
}
function fetchTasks(
    startDate,
    endDate,
    successCallback,
    failureCallback,
    project_id
) {
    $.ajax({
        url: "/master-panel/tasks/get-calendar-data",
        type: "GET",
        data: {
            start: startDate.toISOString(), // Send the start date
            end: endDate.toISOString(), // Send the end date,
            project_id: project_id,
        },
        success: function (response) {
            // Parse and format dynamic data for FullCalendar
            var events = response.map(function (event) {
                return {
                    id: event.id,
                    tasks_info_url: event.tasks_info_url,
                    title: event.title,
                    start: event.start,
                    end: moment(event.end).add(1, "days").format("YYYY-MM-DD"),
                    backgroundColor: event.backgroundColor,
                    borderColor: event.borderColor,
                    textColor: event.textColor,
                };
            });
            // Invoke success callback with dynamic data
            successCallback(events);
        },
        error: function (xhr, status, error) {
            console.error(xhr.responseText);
            // Invoke failure callback if there's an error
            failureCallback(error);
        },
    });
}
// Initialize the calendar on page load
calenderView();
$(document).ready(function () {
    $("#previewToast").click(function () {
        var previewToastPosition = $("#toastPosition").val();
        var toastTimeoutInput = $("#toastTimeout");
        var previewToastTimeout = parseFloat(toastTimeoutInput.val());
        // Validate toast timeout is not blank and is a positive number
        if (isNaN(previewToastTimeout) || previewToastTimeout <= 0) {
            toastr.options = {
                positionClass: toastPosition,
                timeOut: parseFloat(toastTimeOut) * 1000,
                showDuration: "300",
                hideDuration: "1000",
                extendedTimeOut: "1000",
                progressBar: true,
                closeButton: true,
            };
            toastr.error("Please enter a valid timeout value in seconds.");
            toastTimeoutInput.focus();
            return;
        }
        // Convert timeout to milliseconds
        previewToastTimeout *= 1000;
        toastr.options = {
            positionClass: previewToastPosition,
            timeOut: previewToastTimeout,
            showDuration: "300",
            hideDuration: "1000",
            extendedTimeOut: "1000",
            progressBar: true,
            closeButton: true,
        };
        toastr.success(
            "This is a preview of your toast message!",
            "Toast Preview"
        );
    });
});
document.addEventListener("DOMContentLoaded", function () {
    var chatBox = document.getElementById("chatBox");
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
});
// Mention in the text area
function initializeMentionTextarea($textarea) {
    // Extract mention id and type from the data attributes of the textarea
    const mentionID = $textarea.data("mention-id");
    const mentionType = $textarea.data("mention-type");
    // Check if the textarea element exists
    if ($textarea.length === 0) {
        console.error("Textarea not found.");
        return;
    }
    // Initialize Tribute.js with the provided textarea
    const tribute = new Tribute({
        values: function (text, cb) {
            // Fetch users based on the search term and mention info
            $.ajax({
                url: `/master-panel/projects/get-users`,
                method: "GET",
                data: {
                    search: text,
                    mention_id: mentionID,
                    mention_type: mentionType,
                },
                success: function (response) {
                    const mappedUsers = response.map((user) => ({
                        key: user.id, // Use 'id' as key
                        value: user.first_name + " " + user.last_name,
                    }));
                    cb(mappedUsers); // Provide the data to Tribute.js callback
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching users:", error);
                },
            });
        },
        selectTemplate: function (item) {
            return `@${item.original.value}`; // What gets inserted when selected
        },
        lookup: "value", // Attribute used for lookup
        menuItemTemplate: function (item) {
            return `${item.original.value}`; // How items appear in the dropdown
        },
    });
    // Attach Tribute.js to the textarea
    tribute.attach($textarea[0]);
}
function stripHtml(content) {
    // Replace <a> tags with the inner text, but only add '@' if the inner text doesn't already start with it
    return content.replace(
        /<a [^>]+>([^<]+)<\/a>/g,
        function (match, innerText) {
            // Check if the innerText already starts with @
            return innerText.startsWith("@") ? innerText : "@" + innerText;
        }
    );
}
// Initialize the Select2 and Ajax in the select
function initSelect2Ajax(
    elementId,
    ajaxUrl,
    placeholderText = "",
    allowClear = true,
    minimumInputLength = 0,
    initialData = true
) {
    const $element = $(elementId);
    const $modalParent = $element.closest('.modal');

    $element.select2({
        placeholder: placeholderText,
        dropdownParent: $modalParent.length ? $modalParent : $(document.body), // ✅ Conditional handling
        ajax: {
            url: ajaxUrl,
            dataType: "json",
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || "", // Search term
                    page: params.page || 1, // Pagination
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: data.pagination.more,
                    },
                };
            },
            cache: true,
        },
        minimumInputLength: minimumInputLength,
        allowClear: allowClear,
        initSelection: function (element, callback) {
            if (initialData) {
                $.ajax({
                    url: ajaxUrl,
                    data: {
                        q: "",
                        page: 1,
                    },
                    dataType: "json",
                    success: function (data) {
                        callback(data.items);
                    },
                });
            }
        },
    });
}
function initPhoneInput(inputId, initialCountryCode = "", initialISOCode = "") {
    const input = document.querySelector(`#${inputId}`);
    // Create a wrapper and clear button for the input
    const wrapper = document.createElement("div");
    wrapper.style.position = "relative";
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    const clearButton = document.createElement("button");
    clearButton.innerHTML = "✖"; // Use an X or any icon for clear
    clearButton.style.position = "absolute";
    clearButton.style.right = "10px"; // Adjust as needed
    clearButton.style.top = "50%";
    clearButton.style.transform = "translateY(-50%)";
    clearButton.style.border = "none";
    clearButton.style.background = "none";
    clearButton.style.cursor = "pointer";
    clearButton.style.display = "none"; // Initially hidden
    wrapper.appendChild(clearButton);
    const iti = window.intlTelInput(input, {
        utilsScript:
            "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js",
        separateDialCode: true,
        initialCountry: "auto",
        preferredCountries: ["US", "GB", "CA", "IN"],
        geoIpLookup: function (callback) {
            callback("US"); // Default to US for geo IP lookup
        },
    });
    // Show the clear button when input has a value
    input.addEventListener("input", () => {
        clearButton.style.display = input.value ? "block" : "none";
    });
    // Clear input on button click
    clearButton.addEventListener("click", (e) => {
        e.preventDefault();
        iti.setNumber(""); // Clear the input
        clearButton.style.display = "none";
    });
    // Set initial country if provided
    if (initialCountryCode) {
        const allCountries = window.intlTelInputGlobals.getCountryData();
        let country;
        if (initialISOCode) {
            country = allCountries.find(
                (c) =>
                    `+${c.dialCode}` === initialCountryCode &&
                    c.iso2 === initialISOCode
            );
        }
        if (!country) {
            country = allCountries.find(
                (c) => `+${c.dialCode}` === initialCountryCode
            );
        }
        if (country) {
            iti.setCountry(country.iso2);
        }
    }
    function getNumber() {
        return input.value;
    }
    function getCountryCode() {
        const countryData = iti.getSelectedCountryData();
        return countryData.dialCode ? `+${countryData.dialCode}` : "";
    }
    function getISOCode() {
        const countryData = iti.getSelectedCountryData();
        return countryData.iso2 ? countryData.iso2 : "";
    }
    function setNumber(phoneNumber, countryCode) {
        if (countryCode) {
            const allCountries = window.intlTelInputGlobals.getCountryData();
            const country = allCountries.find(
                (c) => `+${c.dialCode}` === countryCode
            );
            if (country) {
                iti.setCountry(country.iso2);
            }
        }
        iti.setNumber(phoneNumber);
    }
    input.phoneInputMethods = {
        getNumber,
        getCountryCode,
        setNumber,
        getISOCode,
        isValidNumber: () => iti.isValidNumber(),
        getValidationError: () => iti.getValidationError(),
    };
    return input.phoneInputMethods;
}
$(function () {
    if ($("#project_users").length) {
        initSelect2Ajax(
            "#project_users",
            "/master-panel/users/search-users",
            label_select_user,
            true,
            0,
            true
        );
    }
    if ($("#user_filter").length) {
        initSelect2Ajax(
            "#user_filter",
            "/master-panel/users/search-users",
            label_select_user,
            true,
            0,
            true
        );
    }
    if ($("#project_clients").length) {
        initSelect2Ajax(
            "#project_clients",
            "/master-panel/clients/search-clients",
            label_select_client,
            true,
            0,
            true
        );
    }
});
$(document).ready(function () {
    $("#generate-password").on("click", function () {
        function generatePassword(length) {
            var charset =
                "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
            var password = "";
            for (var i = 0, n = charset.length; i < length; ++i) {
                password += charset.charAt(Math.floor(Math.random() * n));
            }
            return password;
        }
        // Generate a new random password
        var newPassword = generatePassword(12);
        // Set the generated password in both password and confirm password fields
        $("#password").val(newPassword);
        $("#password_confirmation").val(newPassword);
        // Ensure password is visible after generation
        var passwordField = $("#password");
        var toggleIcon = $(".toggle-password i");
        // Explicitly set the password field type to 'text'
        if (passwordField.attr("type") === "password") {
            passwordField.attr("type", "text"); // Show password
            // Ensure the toggle icon is in 'show' state
            toggleIcon.removeClass("bx-hide").addClass("bx-show");
        }
    });
});
$(document).ready(function () {
    // Handle delete selected notes or todos
    $("#delete-selected").on("click", function () {
        const itemType = $(this).data("type");
        const selectedIds = $(".selected-items:checked")
            .map(function () {
                return $(this).val();
            })
            .get();
        if (selectedIds.length > 0) {
            $("#confirmDeleteSelectedModal").modal("show"); // show the confirmation modal
            $("#confirmDeleteSelectedModal").off(
                "click",
                "#confirmDeleteSelections"
            );
            $("#confirmDeleteSelectedModal").on(
                "click",
                "#confirmDeleteSelections",
                function (e) {
                    $("#confirmDeleteSelections")
                        .html(label_please_wait)
                        .attr("disabled", true);
                    $.ajax({
                        url:
                            "/master-panel" +
                            "/" +
                            itemType +
                            "/destroy_multiple", // Adjust URL based on item type
                        data: {
                            ids: selectedIds,
                        },
                        type: "POST",
                        headers: {
                            "X-CSRF-TOKEN": $('input[name="_token"]').attr(
                                "value"
                            ),
                        },
                        success: function (response) {
                            $("#confirmDeleteSelections")
                                .html(label_yes)
                                .attr("disabled", false);
                            $("#confirmDeleteSelectedModal").modal("hide");
                            location.reload();
                        },
                        error: function (data) {
                            $("#confirmDeleteSelections")
                                .html(label_yes)
                                .attr("disabled", false);
                            $("#confirmDeleteSelectedModal").modal("hide");
                            toastr.error(label_something_went_wrong);
                        },
                    });
                }
            );
        } else {
            toastr.error(label_please_select_records_to_delete);
        }
    });
});
$("#select-all").on("click", function () {
    $(".selected-items").prop("checked", this.checked);
});
$(function () {
    if ($("#birthday_user_filter").length) {
        initSelect2Ajax(
            "#birthday_user_filter",
            "/master-panel/users/search-users",
            label_select_user,
            true,
            0,
            true
        );
    }
    if ($("#wa_user_filter").length) {
        initSelect2Ajax(
            "#wa_user_filter",
            "/master-panel/users/search-users",
            label_select_user,
            true,
            0,
            true
        );
    }
    if ($("#mol_user_filter").length) {
        initSelect2Ajax(
            "#mol_user_filter",
            "/master-panel/users/search-users",
            label_select_user,
            true,
            0,
            true
        );
    }
});
// Check Email Settings Functionality
$(document).on("click", "#test-email-settings", function () {
    const label_test_email_settings = $(this).html();
    $(this).html(label_please_wait).attr("disabled", true);
    $.ajax({
        url: "/superadmin/settings/email/test-email-settings",
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"),
        },
        success: function (response) {
            if (response.error) {
                toastr.error(response.message);
            } else {
                toastr.success(response.message);
            }
            $("#test-email-settings")
                .html(label_test_email_settings)
                .attr("disabled", false);
        },
        error: function (data) {
            $("#test-email-settings")
                .html(label_test_email_settings)
                .attr("disabled", false);
            toastr.error(label_something_went_wrong);
        },
    });
});
function announcementCalendar() {
    const calendarEl = $("#announcements_calendar")[0]; // jQuery selector to get the DOM element

    const editButton = '#editAnnouncementBtn';
    const deleteButton = "#deleteAnnouncementBtn";
    // FullCalendar Initialization
    const calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: ["interaction", "dayGrid", "list"],
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,listYear",
        },
        events: async function (info, successCallback, failureCallback) {
            try {
                // Make AJAX request to fetch dynamic data
                const response = await $.ajax({
                    url: "/master-panel/announcements/events",
                    type: "GET",
                    data: {
                        startDate: info.startStr,
                        endDate: info.endStr,
                    },
                });
                console.log("Raw Response:", response);
                // Parse and format dynamic data for FullCalendar
                const events = response.map(event => ({

                    id: event.id,
                    title: event.title,
                    start: event.start,
                    end: event.end,
                    created_by: event.created_by,
                    backgroundColor: event.backgroundColor,
                    borderColor: event.borderColor || event.backgroundColor, // Fallback
                    textColor: event.textColor,
                    extendedProps: event.extendedProps, // Pass extendedProps directly
                }));
                console.log("Parsed Events:", events);
                // Invoke success callback with dynamic data
                successCallback(events);
            } catch (error) {
                console.error("Error fetching events:", error);
                failureCallback(error); // Call failureCallback on error
            }
        },
        eventClick: function (info) {
            // Show announcement details
            const event = info.event;
            $("#announcementTitle").text(event.title);
            $("#announcementContent").text(event.extendedProps.content);
            $("#announcementPriority").text(event.extendedProps.priority);
            $("#announcementCreatedBy").text(event.extendedProps.createdBy);
            const startDate = moment(event.start).format(js_date_format);
            const endDate = moment(event.end).subtract(1, 'day').format(js_date_format);
            $("#announcementStartDate").text(startDate);
            $("#announcementEndDate").text(endDate);
            const canEdit = $("#canEdit").val();
            const canDelete = $("#canDelete").val();
            const userId = $("#userId").val();
            const isAdminOrHasAllDataAccess = $("#isAdminOrHasAllDataAccess").val();
            // Show action buttons if the user is admin or the creator of the announcement
            $(editButton).data("id", event.id);
            $(deleteButton).data("id", event.id);
            $(editButton).attr("data-id", event.id);
            $(deleteButton).attr("data-id", event.id);
            // Debugging: log data-id values
            console.log("Edit Button data-id:", $(editButton).data("id"));
            console.log("Delete Button data-id:", $(deleteButton).data("id"));
            // Toggle button visibility

            if (canEdit == 1 && userId == event.extendedProps.created_by) {

                $(editButton).removeClass("d-none");
            } else {
                $(editButton).addClass("d-none");
            }
            if (canDelete == 1 || isAdminOrHasAllDataAccess == 1) {
                $(deleteButton).removeClass("d-none");
            } else {
                $(deleteButton).addClass("d-none");
            }

            // Show the modal
            $("#announcementModal").modal("show");
        },
        timezone: 'UTC',  // Adjust the timezone if necessary
    });
    calendar.render();
}
$(document).ready(function () {
    if ($('#announcements_calendar').length) {
        announcementCalendar();
    }
});

// Announcements Update

$(document).on("click", "#mark-all-announcement-as-read", function (e) {
    e.preventDefault();
    $("#mark_all_announcements_as_read_modal").modal("show"); // show the confirmation modal
    $("#mark_all_announcements_as_read_modal").on(
        "click",
        "#confirmAnnouncementMarkAllAsRead",
        function () {
            $("#confirmAnnouncementMarkAllAsRead")
                .html(label_please_wait)
                .attr("disabled", true);
            $.ajax({
                url: "/master-panel/announcements/mark-all-as-read",
                type: "PUT",
                headers: {
                    "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
                },
                success: function (response) {
                    location.reload();

                },
            });
        }
    );
});
$(document).on("click", ".update-announcement-status", function (e) {
    var announcementId = $(this).data("id");
    var needConfirm = $(this).data("needconfirm") || false;
    if (needConfirm) {
        // Show the confirmation modal
        $("#update_announcement_status_modal").modal("show");
        // Attach click event handler to the confirmation button
        $("#update_announcement_status_modal").off(
            "click",
            "#confirmAnnouncementStatus"
        );
        $("#update_announcement_status_modal").on(
            "click",
            "#confirmAnnouncementStatus",
            function () {
                $("#confirmAnnouncementStatus")
                    .html(label_please_wait)
                    .attr("disabled", true);
                performAnnouncementUpdate(announcementId, needConfirm);
            }
        );
    } else {
        // If confirmation is not needed, directly perform the update and handle response
        performAnnouncementUpdate(announcementId);
    }
});
function performAnnouncementUpdate(announcementId, needConfirm = "") {
    $.ajax({
        url: "/master-panel/announcements/update-status",
        type: "PUT",
        data: { id: announcementId, needConfirm: needConfirm },
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        success: function (response) {
            console.log(response);

            if (needConfirm) {
                $("#confirmAnnouncementStatus")
                    .html(label_yes)
                    .attr("disabled", false);
                if (response.error == false) {
                    toastr.success(response.message);
                    $("#table").bootstrapTable("refresh");
                } else {
                    toastr.error(response.message);
                }
                $("#update_announcement_status_modal").modal("hide");
            }
        },
    });
}

if (
    typeof manage_announcements !== "undefined" &&
    manage_announcements == "true"
) {
    function updateUnreadAnnouncements() {
        // Make an AJAX request to fetch the count and HTML of unread announcements
        $.ajax({
            url: "/master-panel/announcements/get-unread-announcements",
            type: "GET",
            dataType: "json",
            success: function (data) {
                const unreadAnnouncementsCount = data.count;
                const unreadAnnouncementsHtml = data.html;
                // Update the count in the badge
                $("#unreadAnnouncementsCount").text(unreadAnnouncementsCount);

                // Update the announcements list with the new HTML
                $("#unreadAnnouncementsContainer").html(unreadAnnouncementsHtml);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching unread announcements:", error);
            },
        });
    }

    updateUnreadAnnouncements();
    // Update the unread announcements every 30 seconds
    setInterval(updateUnreadAnnouncements, 30000);
}

// Task Reminder Settings

$(document).ready(function () {
    // Toggle Reminder Settings Visibility
    $('#reminder-switch').on('change', function () {
        if ($(this).is(':checked')) {
            $('#reminder-settings').removeClass('d-none');
            $('#time-of-day').prop('required', true);
        } else {
            $('#reminder-settings').addClass('d-none');
            $('#time-of-day').prop('required', false);
        }
    });

    // Dynamic Display Based on Frequency Type
    $('#frequency-type').on('change', function () {
        const value = $(this).val();
        $('#day-of-week-group').addClass('d-none');
        $('#day-of-month-group').addClass('d-none');

        if (value === 'weekly') {
            $('#day-of-week-group').removeClass('d-none');
        } else if (value === 'monthly') {
            $('#day-of-month-group').removeClass('d-none');
        }
    });

    $('#edit-reminder-switch').on('change', function () {
        if ($(this).is(':checked')) {
            $('#edit-reminder-settings').removeClass('d-none');
        } else {
            $('#edit-reminder-settings').addClass('d-none');
        }
    });
    $('#edit-frequency-type').on('change', function () {
        const value = $(this).val();
        $('#edit-day-of-week-group').addClass('d-none');
        $('#edit-day-of-month-group').addClass('d-none');

        if (value === 'weekly') {
            $('#edit-day-of-week-group').removeClass('d-none');
        } else if (value === 'monthly') {
            $('#edit-day-of-month-group').removeClass('d-none');
        }
    });
});

// Recurring Task Settings
$(document).ready(function () {
    // Toggle Recurring Task Settings Visibility
    $('#recurring-task-switch').on('change', function () {
        if ($(this).is(':checked')) {
            $('#recurring-task-settings').removeClass('d-none');
            $('#recurrence-frequency').prop('required', true);
            $('#recurrence-starts-from').prop('required', true);
            $('#recurrence-occurrences').prop('required', true);
        } else {
            $('#recurring-task-settings').addClass('d-none');
            $('#recurrence-frequency').prop('required', false);
            $('#recurrence-starts-from').prop('required', false);
            $('#recurrence-occurrences').prop('required', false);
        }
    });

    // Dynamic Display Based on Recurrence Frequency Type
    $('#recurrence-frequency').on('change', function () {
        const value = $(this).val();
        $('#recurrence-day-of-week-group').addClass('d-none');
        $('#recurrence-day-of-month-group').addClass('d-none');
        $('#recurrence-month-of-year-group').addClass('d-none');

        if (value === 'weekly') {
            $('#recurrence-day-of-week-group').removeClass('d-none');
        } else if (value === 'monthly') {
            $('#recurrence-day-of-month-group').removeClass('d-none');
        } else if (value === 'yearly') {
            $('#recurrence-day-of-month-group').removeClass('d-none');
            $('#recurrence-month-of-year-group').removeClass('d-none');
        }
    });

    // Initialize Settings on Page Load
    const initializeSettings = function () {
        if ($('#recurring-task-switch').is(':checked')) {
            $('#recurring-task-settings').removeClass('d-none');
        } else {
            $('#recurring-task-settings').addClass('d-none');
        }

        const value = $('#recurrence-frequency').val();
        if (value === 'weekly') {
            $('#recurrence-day-of-week-group').removeClass('d-none');
        } else if (value === 'monthly') {
            $('#recurrence-day-of-month-group').removeClass('d-none');
        } else if (value === 'yearly') {
            $('#recurrence-day-of-month-group').removeClass('d-none');
            $('#recurrence-month-of-year-group').removeClass('d-none');
        }
    };

    initializeSettings();
});

//Edit Recurring Task Settings
$(document).ready(function () {
    // Toggle Recurring Task Settings Visibility
    $('#edit-recurring-task-switch').on('change', function () {
        if ($(this).is(':checked')) {
            $('#edit-recurring-task-settings').removeClass('d-none');
            $('#edit-recurrence-frequency').prop('required', true);
            $('#edit-recurrence-starts-from').prop('required', true);
            $('#edit-recurrence-occurrences').prop('required', true);
        } else {
            $('#edit-recurring-task-settings').addClass('d-none');
            $('#edit-recurrence-frequency').prop('required', false);
            $('#edit-recurrence-starts-from').prop('required', false);
            $('#edit-recurrence-occurrences').prop('required', false);
        }
    });

    // Dynamic Display Based on Recurrence Frequency Type
    $('#edit-recurrence-frequency').on('change', function () {
        const value = $(this).val();
        $('#edit-recurrence-day-of-week-group').addClass('d-none');
        $('#edit-recurrence-day-of-month-group').addClass('d-none');
        $('#edit-recurrence-month-of-year-group').addClass('d-none');

        if (value === 'weekly') {
            $('#edit-recurrence-day-of-week-group').removeClass('d-none');
        } else if (value === 'monthly') {
            $('#edit-recurrence-day-of-month-group').removeClass('d-none');
        } else if (value === 'yearly') {
            $('#edit-recurrence-day-of-month-group').removeClass('d-none');
            $('#edit-recurrence-month-of-year-group').removeClass('d-none');
        }
    });

    // Initialize Settings on Page Load
    const editinitializeSettings = function () {
        if ($('#edit-recurring-task-switch').is(':checked')) {
            $('#edit-recurring-task-settings').removeClass('d-none');
        } else {
            $('#edit-recurring-task-settings').addClass('d-none');
        }

        const value = $('#edit-recurrence-frequency').val();
        if (value === 'weekly') {
            $('#edit-recurrence-day-of-week-group').removeClass('d-none');
        } else if (value === 'monthly') {
            $('#edit-recurrence-day-of-month-group').removeClass('d-none');
        } else if (value === 'yearly') {
            $('#edit-recurrence-day-of-month-group').removeClass('d-none');
            $('#edit-recurrence-month-of-year-group').removeClass('d-none');
        }
    };

    editinitializeSettings();
});
$(function () {
    if ($('.project-select').length > 0) {
        initSelect2Ajax('.project-select', 'tasks/search-projects', label_project);
    }
})

$(document).on("click", ".edit-task-list", function () {
    var id = $(this).data("id");
    var routePrefix = $("#table").data("routePrefix");
    $("#edit_task_list_modal").modal("show");
    $.ajax({
        url: "/master-panel/task-lists/get/" + id,
        type: "get",
        headers: {
            "X-CSRF-TOKEN": $('input[name="_token"]').attr("value"), // Replace with your method of getting the CSRF token
        },
        dataType: "json",
        success: function (response) {
            console.log(response.task_list.id);

            $("#task_list_id").val(response.task_list.id);
            $("#task_list_project").val(response.task_list.project.title);
            $("#task_list_name").val(response.task_list.name);
            var projectData = {
                id: response.task_list.project.id, // Ensure this matches your project's ID field
                text: response.task_list.project.title, // Ensure this matches your project's title field
            };

            var $projectSelect = $("#task_list_project_id");
            $projectSelect.empty().append(new Option(projectData.text, projectData.id, true, true)).trigger("change");
        },
    });
});

// Show the Active Tab
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (button) {
    button.addEventListener("click", function () {
        // Check if the clicked button has the "list-button" or "calendar-button" class
        if (
            this.classList.contains("list-button") ||
            this.classList.contains("calendar-button")
        ) {
            // Remove bg-primary and text-white from "List" and "Calendar" buttons only
            document
                .querySelectorAll(".list-button, .calendar-button")
                .forEach(function (specificButton) {
                    specificButton.classList.remove("bg-primary", "text-white");
                });

            // Add bg-primary and text-white to the clicked button
            this.classList.add("bg-primary", "text-white");
        }

        // Handle nested tabs
        const parentPane = document.querySelector(this.dataset.bsTarget);
        if (parentPane) {
            parentPane
                .querySelectorAll(".list-button, .calendar-button")
                .forEach(function (subTab) {
                    subTab.classList.remove("bg-primary", "text-white");
                });

            const activeSubTab = parentPane.querySelector(
                ".list-button.active, .calendar-button.active"
            );
            if (activeSubTab) {
                activeSubTab.classList.add("bg-primary", "text-white");
            }
        }
    });
});
