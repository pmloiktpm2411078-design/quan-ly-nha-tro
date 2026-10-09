<!-- ==================================================
     GHI NHẬN HƯ HỎNG
================================================== -->

<h3>Ghi nhận hư hỏng</h3>

<p>
    <label>
        <input
            type="checkbox"
            id="no_damage"
            name="no_damage"
            value="1"
            onchange="toggleDamageForm()"
        >
        Không phát hiện hư hỏng
    </label>
</p>

<div id="damage_form">

    <p>
        <label>
            Nguyên nhân hư hỏng:
        </label>

        <br>

        <select
            name="damage_cause"
            id="damage_cause"
            onchange="updateDamageSupport()"
        >

            <option value="">
                -- Chọn nguyên nhân --
            </option>

            <option value="LANDLORD">
                Do chủ trọ
            </option>

            <option value="TENANT">
                Do người thuê
            </option>

            <option value="OTHER">
                Chưa xác định / Khác
            </option>

        </select>
    </p>


    <p>
        <label>
            Mức độ hư hỏng:
        </label>

        <br>

        <select
            name="damage_severity"
            id="damage_severity"
            onchange="updateDamageSupport()"
        >

            <option value="">
                -- Chọn mức độ --
            </option>

            <option value="LOW">
                Nhẹ
            </option>

            <option value="MEDIUM">
                Trung bình
            </option>

            <option value="HIGH">
                Nặng
            </option>

            <option value="CRITICAL">
                Rất nghiêm trọng
            </option>

        </select>
    </p>


    <p>
        <label>
            Mô tả hư hỏng:
        </label>

        <br>

        <textarea
            name="damage_description"
            id="damage_description"
            rows="4"
            cols="50"
            placeholder="Nhập mô tả hư hỏng..."
        ></textarea>
    </p>


    <p>
        <label>
            Tỷ lệ người thuê hỗ trợ:
        </label>

        <br>

        <input
            type="number"
            name="tenant_support_percent"
            id="tenant_support_percent"
            value="0"
            readonly
        > %
    </p>


    <p>
        <label>
            Chi phí xử lý hư hỏng:
        </label>

        <br>

        <input
            type="number"
            name="damage_cost"
            id="damage_cost"
            value="0"
            min="0"
            step="1000"
            oninput="calculateTenantSupport()"
        >
        VNĐ
    </p>


    <p>
        <label>
            Số tiền người thuê hỗ trợ:
        </label>

        <br>

        <input
            type="number"
            name="tenant_support_amount"
            id="tenant_support_amount"
            value="0"
            readonly
        >
        VNĐ
    </p>

</div>


<script>

function toggleDamageForm() {

    const checkbox =
        document.getElementById("no_damage");

    const form =
        document.getElementById("damage_form");

    if (checkbox.checked) {

        form.style.display = "none";

        document.getElementById("damage_cause").value = "";

        document.getElementById("damage_severity").value = "";

        document.getElementById("damage_description").value = "";

        document.getElementById("tenant_support_percent").value = 0;

        document.getElementById("damage_cost").value = 0;

        document.getElementById("tenant_support_amount").value = 0;

    } else {

        form.style.display = "block";
    }
}


function updateDamageSupport() {

    const cause =
        document.getElementById("damage_cause").value;

    const severity =
        document.getElementById("damage_severity").value;

    const percentInput =
        document.getElementById("tenant_support_percent");

    /*
     * Nếu hư hỏng do chủ trọ:
     * Chủ trọ tự chịu chi phí.
     */
    if (cause === "LANDLORD") {

        percentInput.value = 0;

        document.getElementById("tenant_support_amount").value = 0;

        return;
    }


    /*
     * Nếu chưa xác định:
     * Chưa tính phần người thuê hỗ trợ.
     */
    if (cause === "OTHER") {

        percentInput.value = 0;

        document.getElementById("tenant_support_amount").value = 0;

        return;
    }


    /*
     * Nếu do người thuê:
     * Tự động xác định % theo mức độ.
     */

    let percent = 0;


    if (cause === "TENANT") {

        switch (severity) {

            case "LOW":
                percent = 10;
                break;

            case "MEDIUM":
                percent = 30;
                break;

            case "HIGH":
                percent = 50;
                break;

            case "CRITICAL":
                percent = 70;
                break;

            default:
                percent = 0;
        }
    }


    percentInput.value = percent;

    calculateTenantSupport();
}


function calculateTenantSupport() {

    const percent =
        parseFloat(
            document.getElementById("tenant_support_percent").value
        ) || 0;

    const cost =
        parseFloat(
            document.getElementById("damage_cost").value
        ) || 0;

    const amount =
        cost * percent / 100;

    document.getElementById("tenant_support_amount").value =
        Math.round(amount);
}


toggleDamageForm();

</script>