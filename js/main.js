const { PDFDocument } = window.PDFLib;
var _signaturePad;
let _orgName, _orgCIF, _orgIBAN, _percent;
let _pluginUrl;
// Representative (Imputernicit) fields
let _imputernicit_nume, _imputernicit_cui, _imputernicit_strada, _imputernicit_numar;
let _imputernicit_ap, _imputernicit_judet, _imputernicit_localitate;
let _imputernicit_telefon, _imputernicit_email;

window.onload = function () {
    var canvas = document.getElementById("signature-pad");
    _signaturePad = new SignaturePad(canvas, { penColor: "rgb(255, 255, 255)" });
    GetAndSetConfig();
    SetVisibility("mainDiv", true);
    // Auto-start: show form directly, skip info page
    Start();
    // Statistics disabled in WordPress - uncomment if needed
    // AddStatistic("Visits");
};

function GetAndSetConfig() {
    // Use config from WordPress localize_script
    if (typeof formularFillConfig !== 'undefined') {
        _pluginUrl = formularFillConfig.pluginUrl;
        _orgName = formularFillConfig.orgName;
        _orgCIF = formularFillConfig.orgCIF;
        _orgIBAN = formularFillConfig.orgIBAN;
        _percent = formularFillConfig.percent;
        
        // Representative (Imputernicit) fields
        _imputernicit_nume = formularFillConfig.imputernicit_nume || '';
        _imputernicit_cui = formularFillConfig.imputernicit_cui || '';
        _imputernicit_strada = formularFillConfig.imputernicit_strada || '';
        _imputernicit_numar = formularFillConfig.imputernicit_numar || '';
        _imputernicit_ap = formularFillConfig.imputernicit_ap || '';
        _imputernicit_judet = formularFillConfig.imputernicit_judet || '';
        _imputernicit_localitate = formularFillConfig.imputernicit_localitate || '';
        _imputernicit_telefon = formularFillConfig.imputernicit_telefon || '';
        _imputernicit_email = formularFillConfig.imputernicit_email || '';
        
        document.title = `Formular 230 ANAF pentru ${_orgName}`;
        document.getElementById("infoTitle").innerHTML = `Formular 230 ANAF pentru ${_orgName}`;
        
        // Update the placeholder text for organization name
        const pInfo1 = document.getElementById("pInfo1");
        const orgNamePlaceholder = pInfo1.querySelector('.org-name-placeholder');
        if (orgNamePlaceholder) {
            orgNamePlaceholder.textContent = _orgName;
        }
    } else {
        // Fallback for testing - try to fetch config.json
        console.log('Loading config.json...');
        fetch('config.json')
            .then((response) => {
                console.log('Config response status:', response.status);
                return response.json();
            })
            .then(
                function(jsonData) {
                    console.log('Config loaded:', jsonData);
                    _orgName = jsonData.OrgName;
                    _orgCIF = jsonData.OrgCIF;
                    _orgIBAN = jsonData.OrgIBAN;
                    _percent = jsonData.Percent;
                    _pluginUrl = '';
                    // Representative (Imputernicit) fields
                    _imputernicit_nume = jsonData.imputernicit_nume || '';
                    _imputernicit_cui = jsonData.imputernicit_cui || '';
                    _imputernicit_strada = jsonData.imputernicit_strada || '';
                    _imputernicit_numar = jsonData.imputernicit_numar || '';
                    _imputernicit_ap = jsonData.imputernicit_ap || '';
                    _imputernicit_judet = jsonData.imputernicit_judet || '';
                    _imputernicit_localitate = jsonData.imputernicit_localitate || '';
                    _imputernicit_telefon = jsonData.imputernicit_telefon || '';
                    _imputernicit_email = jsonData.imputernicit_email || '';
                    console.log('Imputernicit config loaded:', _imputernicit_nume, _imputernicit_cui);
                    document.title = `Formular 230 ANAF pentru ${_orgName}`;
                    document.getElementById("infoTitle").innerHTML = `Formular 230 ANAF pentru ${_orgName}`;
                    let pInfo1 = document.getElementById("pInfo1");
                    pInfo1.innerHTML = pInfo1.innerHTML.replace("{{OrgName}}", _orgName);
                }
            )
            .catch((error) => {
                console.error('Error loading config.json:', error);
            });
    }
}

function Start() {
    SetVisibility("info", false);
    SetVisibility("form", true);
}

function ClearSignature() {
    _signaturePad.clear();
}

function ShowModalMessage(msgId) {
    let message = "";
    if (msgId == 1) { message = "Formularul tău a fost generat cu succes!<br />Descărcarea lui a început."; }
    else if (msgId == 2) { message = "Te rog să completezi toate câmpurile obligatorii!"; }
    else if (msgId == 3) { message = "Te rog să te semnezi!"; }
    else { message = `A apărut o eroare:<br />${msgId}`; }
    document.getElementById("mainModalMessage").innerHTML = message;
    let mainModal = new bootstrap.Modal(document.getElementById("mainModal"), {});
    mainModal.show();
}

async function Generate() {
    try {
        console.log("Generate started...");
        if (_signaturePad.isEmpty()) {
            ShowModalMessage(3);
            return;
        }

        // Determine base URL for assets
        const baseUrl = (typeof _pluginUrl !== 'undefined' && _pluginUrl) ? _pluginUrl : '';
        const formUrl = baseUrl + 'assets/Formular230.pdf';
        const fontUrl = baseUrl + 'assets/MyriadPro-regular.otf';
        
        console.log("Loading PDF from:", formUrl);
        
        const formPdfBytes = await fetch(formUrl).then(res => res.arrayBuffer());
        const pdfDoc = await PDFDocument.load(formPdfBytes);
        const form = pdfDoc.getForm();

        pdfDoc.registerFontkit(fontkit);
        const fontBytes = await fetch(fontUrl).then(res => res.arrayBuffer());
        const customFont = await pdfDoc.embedFont(fontBytes);
        const rawUpdateFieldAppearances = form.updateFieldAppearances.bind(form);
        form.updateFieldAppearances = function () {
            return rawUpdateFieldAppearances(customFont);
        };

        let nume = document.getElementById("nume").value;
        let prenume = document.getElementById("prenume").value;

        form.getTextField('an').setText((new Date().getFullYear() - 1).toString());
        form.getTextField('nume').setText(nume);
        form.getTextField('prenume').setText(prenume);
        form.getTextField('initiala').setText(document.getElementById("initialaTatalui").value);
        form.getTextField('cnp').setText(document.getElementById("cnp").value);
        form.getTextField('mail').setText(document.getElementById("email").value);
        form.getTextField('telefon').setText(document.getElementById("telefon").value);
        form.getTextField('strada').setText(document.getElementById("strada").value);
        form.getTextField('nr').setText(document.getElementById("nrStrada").value);
        form.getTextField('bloc').setText(document.getElementById("bloc").value);
        form.getTextField('scara').setText(document.getElementById("scara").value);
        form.getTextField('etaj').setText(document.getElementById("etaj").value);
        form.getTextField('apartament').setText(document.getElementById("apartament").value);
        form.getTextField('judet').setText(document.getElementById("judet").value);
        form.getTextField('localitate').setText(document.getElementById("localitate").value);
        form.getTextField('zip').setText(document.getElementById("codPostal").value);
        if (document.getElementById("doiAni").checked) { form.getTextField('doi_ani').setText('X'); }
        form.getTextField('target_cif').setText(_orgCIF);
        form.getTextField('target_name').setText(_orgName);
        form.getTextField('target_iban').setText(_orgIBAN);
        form.getTextField('a5').setText(_percent);
        
        // Representative (Imputernicit) fields
        console.log('Setting imputernicit fields:', _imputernicit_nume, _imputernicit_cui);
        if (_imputernicit_nume) form.getTextField('imputernicit_nume').setText(_imputernicit_nume);
        if (_imputernicit_cui) form.getTextField('imputernicit_cui').setText(_imputernicit_cui);
        if (_imputernicit_strada) form.getTextField('imputernicit_strada').setText(_imputernicit_strada);
        if (_imputernicit_numar) form.getTextField('imputernicit_numar').setText(_imputernicit_numar);
        if (_imputernicit_ap) form.getTextField('imputernicit_ap').setText(_imputernicit_ap);
        if (_imputernicit_judet) form.getTextField('imputernicit_judet').setText(_imputernicit_judet);
        if (_imputernicit_localitate) form.getTextField('imputernicit_localitate').setText(_imputernicit_localitate);
        if (_imputernicit_telefon) form.getTextField('imputernicit_telefon').setText(_imputernicit_telefon);
        if (_imputernicit_email) form.getTextField('imputernicit_email').setText(_imputernicit_email);

        form.flatten();
        const pages = pdfDoc.getPages();
        const firstPage = pages[0];
        UpdateSignaturePadColor("rgb(0, 0, 0)");

        const signatureData = _signaturePad.toDataURL();
        const pngSignature = await pdfDoc.embedPng(signatureData);
        const pngDims = pngSignature.scale(0.4);

        firstPage.drawImage(pngSignature, {
            x: 125,
            y: 105,
            width: pngDims.width,
            height: pngDims.height,
        });
        UpdateSignaturePadColor("rgb(255, 255, 255)");

        const pdfBytes = await pdfDoc.save();
        ShowModalMessage(1);
        download(pdfBytes, `Formular 230 ${prenume} ${nume}.pdf`, "application/pdf");
        // Statistics disabled in WordPress - uncomment if needed
        // AddStatistic("Generations");
    }
    catch (error) {
        console.log(error);
        ShowModalMessage(error.message);
    }
}

function UpdateSignaturePadColor(color) {
    _signaturePad.penColor = color;
    const data = _signaturePad.toData();
    _signaturePad.fromData(data.map(d => {
        d.penColor = color;
        return d;
    }));
}

function AddStatistic(statisticId) {
    if (statisticId == null) { return; }
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == XMLHttpRequest.DONE) {
            if (xmlhttp.status != 200) {
                console.log(`Error: AddStatistic returned ${xmlhttp.status}`);
            }
        }
    };
    xmlhttp.open("POST", `stats.php?id=${statisticId}`, true);
    xmlhttp.setRequestHeader("X-GUID", GenerateGUID());
    xmlhttp.send();
}

function GenerateGUID() {
    try {
        return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        );
    } catch (error) {
        return "QWxleCBWaXJsYW4=";
    }
}

function SetVisibility(elementId, visible) {
    if (visible) {
        document.getElementById(elementId).classList.remove("d-none");
    }
    else {
        document.getElementById(elementId).classList.add("d-none");
    }
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (isValid) {
                Generate();
            } else {
                ShowModalMessage(2);
            }
        });
    }
});
