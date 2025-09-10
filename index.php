    <?php
    session_start();

    // File paths
    $formFile = "form_structure.json";
    $responseFile = "responses.json";

    // Helper: Load form fields
    function loadForm($formFile) {
        return file_exists($formFile) ? json_decode(file_get_contents($formFile), true) : [];
    }

    // Handle saving form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_form'])) {
        $form = $_POST['fields'] ?? [];
        // Flatten options array to comma-separated string
        foreach($form as &$f){
            if(isset($f['options']) && is_array($f['options'])){
                $f['options'] = implode(',', array_map('trim', $f['options']));
            }
        }
        file_put_contents($formFile, json_encode($form, JSON_PRETTY_PRINT));
        $_SESSION['msg'] = "Form saved successfully!";
        header("Location: index.php?mode=create");
        exit;
    }

    // Handle form submission (responses)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_form'])) {
        $fields = loadForm($formFile);
        $errors = [];
        $data = [];

        foreach ($fields as $f) {
            $name = $f['name'];
            $required = $f['required'] == "1";

            if ($f['type'] == 'checkbox') {
                $value = isset($_POST[$name]) ? $_POST[$name] : [];
            } else {
                $value = trim($_POST[$name] ?? '');
            }

            if ($required && (empty($value) && $value !== "0")) {
                $errors[$name] = $f['label'] . " is required.";
            }

            if ($f['type'] == "email" && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$name] = "Invalid email format.";
            }

            $data[$name] = $value;
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header("Location: index.php?mode=form");
            exit;
        }

        $responses = file_exists($responseFile) ? json_decode(file_get_contents($responseFile), true) : [];
        $responses[] = $data;
        file_put_contents($responseFile, json_encode($responses, JSON_PRETTY_PRINT));

        $_SESSION['msg'] = "Form submitted successfully!";
        header("Location: index.php?mode=form");
        exit;
    }

    // Handle reset responses
    if (($_GET['mode'] ?? '') === 'reset_responses') {
        if (file_exists($responseFile)) {
            unlink($responseFile);
        }
        $_SESSION['msg'] = "All responses cleared!";
        header("Location: index.php?mode=responses");
        exit;
    }

    // Handle reset form
    if (($_GET['mode'] ?? '') === 'reset_form') {
        if (file_exists($formFile)) {
            unlink($formFile);
        }
        $_SESSION['msg'] = "Form structure cleared!";
        header("Location: index.php?mode=create");
        exit;
    }

    // MODE SWITCH
    $mode = $_GET['mode'] ?? 'home';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <title>Dynamic Form System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body { background: linear-gradient(135deg, #a1c4fd, #c2e9fb); min-height:100vh; font-family:Arial, sans-serif; padding:50px 0; }
    .container-main { max-width:1200px; margin:auto; }
    .alert-success { margin-bottom:20px; }

    /* Home cards */
    .home-box { display:flex; flex-wrap:wrap; justify-content:center; gap:25px; background:#fff; padding:20px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
    .card-main { background:#f8f9fa; width:220px; height:250px; border-radius:15px; padding:20px; text-align:center; display:flex; flex-direction:column; justify-content:center; align-items:center; transition: transform 0.3s ease, box-shadow 0.3s ease; text-decoration:none; color:inherit; }
    .card-main:hover { transform: scale(1.05); box-shadow:0 10px 25px rgba(0,0,0,0.2); }
    .card-main i { font-size:3rem; margin-bottom:15px; color:#0d6efd; }
    .card-title { font-size:1.2rem; font-weight:bold; }
    .card-subtitle { font-size:0.9rem; color:#6c757d; margin-top:5px; }

    /* Button animations */
    .btn-animate { transition: all 0.3s ease; }
    .btn-animate:hover { transform: scale(1.05); box-shadow:0 4px 15px rgba(0,0,0,0.2); }

    /* Main card for pages */
    .main-card { background:#fff; border-radius:15px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.1); margin-bottom:25px; }

    /* Form creation */
    #create-form-section { display:flex; gap:20px; flex-wrap:wrap; }
    .field-item { border:1px solid #ddd; border-radius:10px; padding:15px; margin-bottom:10px; background:#fff; }
    .option-list input { margin-bottom:5px; }

    /* About page social icons */
    .social-icons { display:flex; justify-content:center; gap:15px; margin-top:20px; }
    .social-icons i { font-size:2.5rem; transition: transform 0.3s, box-shadow 0.3s; }
    .social-icons i:hover { transform: scale(1.2); box-shadow:0 4px 15px rgba(0,0,0,0.2); }
    /* Heading CSS */
    h1.page-heading {
        text-align: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: #0d6efd;
        margin-bottom: 40px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    </style>
    </head>
    <body>
    <div class="container-main">
    <h1 class="page-heading">Form Generator - PHP</h1>

    <?php if(!empty($_SESSION['msg'])): ?>
        <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <?php if($mode==='home'): ?>
    <div class="home-box">
        <a href="index.php?mode=create" class="card-main"><i class="bi bi-pencil-square"></i><div class="card-title">Create Form</div><div class="card-subtitle">Design your form</div></a>
        <a href="index.php?mode=form" class="card-main"><i class="bi bi-journal-text"></i><div class="card-title">Fill Form</div><div class="card-subtitle">Submit your data</div></a>
        <a href="index.php?mode=responses" class="card-main"><i class="bi bi-table"></i><div class="card-title">View Responses</div><div class="card-subtitle">Check submissions</div></a>
        <a href="index.php?mode=reset_form" class="card-main" onclick="return confirm('Delete form structure?')"><i class="bi bi-exclamation-triangle"></i><div class="card-title">Reset Form</div></a>
        <a href="index.php?mode=reset_responses" class="card-main" onclick="return confirm('Clear all responses?')"><i class="bi bi-trash"></i><div class="card-title">Reset Responses</div></a>
        <a href="index.php?mode=about" class="card-main"><i class="bi bi-person-circle"></i><div class="card-title">About Me</div></a>
    </div>

    <?php elseif($mode==='about'): ?>
    <a href="index.php" class="btn btn-outline-primary btn-sm btn-animate mb-3"><i class="bi bi-house-door"></i> Back to Home</a>
    <div class="main-card text-center" style="max-width:500px;margin:auto;">
        <i class="bi bi-person-circle" style="font-size:4rem;color:#0d6efd;margin-bottom:15px;"></i>
        <div class="card-title">SUDARSHAN KUMAR</div>
        <div class="card-subtitle mb-3" style="color:#6c757d;font-size:0.95rem;">
            Lovely Professional University<br>
            Punjab, India<br><br>
            Lovely Professional University (LPU) is a renowned institution offering a wide range of undergraduate and postgraduate programs with modern infrastructure, global collaborations, and vibrant campus life.
        </div>
        <div class="social-icons">
            <a href="https://www.linkedin.com/in/sudarshankumarpvt" target="_blank"><i class="bi bi-linkedin btn-animate"></i></a>
            <a href="https://www.instagram.com/sudarshan251" target="_blank"><i class="bi bi-instagram btn-animate"></i></a>
            <a href="https://www.facebook.com/share/174hvY2jsk/" target="_blank"><i class="bi bi-facebook btn-animate"></i></a>
            <a href="https://youtube.com/@sudarshankumar7240?si=x1ZxfP1ELtlXDv2r" target="_blank"><i class="bi bi-youtube btn-animate"></i></a>
            <a href="https://github.com/sudarshankumarpvt" target="_blank"><i class="bi bi-github btn-animate"></i></a>
            <!-- <a href="https://x.com/sudarshan" target="_blank"><i class="bi bi-x-lg btn-animate"></i></a> -->
        </div>
    </div>

    <?php elseif($mode==='create'): ?>
    <?php $existing = loadForm($formFile); ?>

    <a href="index.php" class="btn btn-outline-primary btn-sm btn-animate mb-3"><i class="bi bi-house-door"></i> Back to Home</a>

    <div id="create-form-section" class="d-flex gap-3 flex-wrap">
        <form id="create-form-wrapper" method="post" style="flex:1; min-width:300px;">
            <div class="mb-3 d-flex gap-2">
                <button type="button" class="btn btn-secondary btn-animate" onclick="addField()">Add Field</button>
                <button type="button" class="btn btn-danger btn-animate" onclick="deleteLastField()">Delete Field</button>
                <button type="submit" name="save_form" class="btn btn-primary btn-animate">Save Form</button>
            </div>

            <?php if($existing): foreach($existing as $i=>$f): ?>
            <div class="field-item" data-index="<?= $i ?>">
                <label class="form-label">Field Label</label>
                <input type="text" class="form-control mb-2 field-label" name="fields[<?= $i ?>][label]" value="<?= htmlspecialchars($f['label']) ?>" required>

                <label class="form-label">Field Name (unique)</label>
                <input type="text" class="form-control mb-2" name="fields[<?= $i ?>][name]" value="<?= htmlspecialchars($f['name']) ?>" required>

                <label class="form-label">Field Type</label>
                <select class="form-select mb-2 field-type" name="fields[<?= $i ?>][type]" onchange="toggleOptionBtn(<?= $i ?>)">
                    <?php foreach(["text","email","password","dropdown","checkbox"] as $t): ?>
                        <option value="<?= $t ?>" <?= $f['type']==$t?"selected":"" ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label">Required?</label>
                <select class="form-select mb-2" name="fields[<?= $i ?>][required]">
                    <option value="1" <?= $f['required']=="1"?"selected":"" ?>>Yes</option>
                    <option value="0" <?= $f['required']=="0"?"selected":"" ?>>No</option>
                </select>

                <div class="option-list mb-2">
                    <?php if(isset($f['options'])): 
                        foreach(explode(",",$f['options']) as $opt): ?>
                        <div class="input-group mb-1">
                            <input type="text" class="form-control" name="fields[<?= $i ?>][options][]" value="<?= htmlspecialchars($opt) ?>" required>
                            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.remove(); updatePreview()">Remove</button>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <button class="btn btn-secondary btn-sm mb-2 add-option-btn" type="button" onclick="addOption(<?= $i ?>)">Add Option</button>
            </div>
            <?php endforeach; endif; ?>
        </form>

        <div id="live-preview" style="flex:1; min-width:300px; background:#f8f9fa; padding:20px; border-radius:15px; max-height:80vh; overflow-y:auto;">
            <h5>Live Preview</h5>
            <form id="preview-form"></form>
        </div>
    </div>

    <script>
    let count = <?= $existing ? count($existing) : 0 ?>;

    function addField(){
        const i = count++;
        const div = document.createElement("div");
        div.className="field-item"; div.dataset.index=i;
        div.innerHTML=`
            <label class="form-label">Field Label</label>
            <input type="text" class="form-control mb-2 field-label" name="fields[${i}][label]" required>

            <label class="form-label">Field Name (unique)</label>
            <input type="text" class="form-control mb-2" name="fields[${i}][name]" required>

            <label class="form-label">Field Type</label>
            <select class="form-select mb-2 field-type" name="fields[${i}][type]" onchange="toggleOptionBtn(${i})">
                <option value="text">Text</option>
                <option value="email">Email</option>
                <option value="password">Password</option>
                <option value="dropdown">Dropdown</option>
                <option value="checkbox">Checkbox</option>
            </select>

            <label class="form-label">Required?</label>
            <select class="form-select mb-2" name="fields[${i}][required]">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>

            <div class="option-list mb-2"></div>
            <button class="btn btn-secondary btn-sm mb-2 add-option-btn" type="button" onclick="addOption(${i})">Add Option</button>
        `;
        document.getElementById("create-form-wrapper").appendChild(div);
        addFieldListeners();
        toggleOptionBtn(i);
        updatePreview();
    }

    function deleteLastField(){
        const fields = document.querySelectorAll(".field-item");
        if(fields.length>0){
            fields[fields.length-1].remove();
            updatePreview();
        }
    }

    function addOption(i){
        const field = document.querySelector(`.field-item[data-index='${i}'] .option-list`);
        const div = document.createElement("div");
        div.className="input-group mb-1";
        div.innerHTML=`
            <input type="text" class="form-control" name="fields[${i}][options][]" required>
            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.remove(); updatePreview()">Remove</button>
        `;
        field.appendChild(div);
        updatePreview();
    }

    function toggleOptionBtn(i){
        const field = document.querySelector(`.field-item[data-index='${i}']`);
        const type = field.querySelector(".field-type").value;
        const btn = field.querySelector(".add-option-btn");
        if(type=="dropdown" || type=="checkbox") btn.style.display="inline-block";
        else {
            btn.style.display="none";
            field.querySelector(".option-list").innerHTML="";
        }
        updatePreview();
    }

    function addFieldListeners(){
        document.querySelectorAll(".field-label, .field-type").forEach(el=>{ el.oninput = updatePreview; });
    }

    function updatePreview(){
        const preview = document.getElementById("preview-form");
        preview.innerHTML="";
        document.querySelectorAll(".field-item").forEach(div=>{
            const type = div.querySelector(".field-type").value;
            const label = div.querySelector(".field-label").value;
            const required = div.querySelector("select[name*='required']").value=="1";

            const wrapper = document.createElement("div"); wrapper.className="mb-3";
            const lbl = document.createElement("label"); lbl.className="form-label"; lbl.textContent = label + (required?"*":"");
            wrapper.appendChild(lbl);

            if(type=="dropdown"){
                const sel = document.createElement("select"); sel.className="form-select"; if(required) sel.required=true;
                const opt0 = document.createElement("option"); opt0.text="-- Select --"; opt0.value=""; sel.appendChild(opt0);
                div.querySelectorAll(".option-list input").forEach(o=>{
                    const opt = document.createElement("option"); opt.value=o.value; opt.text=o.value; sel.appendChild(opt);
                });
                wrapper.appendChild(sel);
            } else if(type=="checkbox"){
                div.querySelectorAll(".option-list input").forEach(o=>{
                    const divc = document.createElement("div"); divc.className="form-check";
                    const inp = document.createElement("input"); inp.type="checkbox"; inp.className="form-check-input"; inp.value=o.value;
                    const lblc = document.createElement("label"); lblc.className="form-check-label"; lblc.textContent=o.value;
                    divc.appendChild(inp); divc.appendChild(lblc); wrapper.appendChild(divc);
                });
            } else {
                const inp = document.createElement("input"); inp.type=type; inp.className="form-control"; if(required) inp.required=true;
                wrapper.appendChild(inp);
            }
            preview.appendChild(wrapper);
        });
    }

    addFieldListeners();
    document.querySelectorAll(".field-item").forEach((div,i)=>{ toggleOptionBtn(i); });
    updatePreview();
    </script>

    <?php elseif($mode==='form'): ?>
    <?php
    $fields = loadForm($formFile);
    if(!$fields){ echo "<div class='alert alert-warning'>No form created yet. <a href='index.php?mode=create'>Create one</a></div>"; }
    else{
    $errors=$_SESSION['errors']??[]; $old=$_SESSION['old']??[]; unset($_SESSION['errors'],$_SESSION['old']);
    ?>
    <a href="index.php" class="btn btn-outline-primary btn-sm btn-animate mb-3"><i class="bi bi-house-door"></i> Back to Home</a>
    <div class="main-card">
        <h3>Fill Form</h3>
        <form method="post">
            <?php foreach($fields as $f): ?>
            <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars($f['label']) ?> <?= $f['required']?'*':'' ?></label>
                <?php if($f['type']=='dropdown'): ?>
                    <select class="form-select" name="<?= htmlspecialchars($f['name']) ?>" <?= $f['required']?'required':'' ?>>
                        <option value="">-- Select --</option>
                        <?php foreach(explode(",",$f['options'] ?? '') as $opt): ?>
                            <option value="<?= trim($opt) ?>" <?= (isset($old[$f['name']]) && $old[$f['name']]==trim($opt))?'selected':'' ?>><?= trim($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif($f['type']=='checkbox'): ?>
                    <?php foreach(explode(",",$f['options'] ?? '') as $opt): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars($f['name']) ?>[]" value="<?= trim($opt) ?>" <?= (isset($old[$f['name']]) && in_array(trim($opt),(array)$old[$f['name']]))?'checked':'' ?>>
                            <label class="form-check-label"><?= trim($opt) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <input type="<?= htmlspecialchars($f['type']) ?>" class="form-control" name="<?= htmlspecialchars($f['name']) ?>" value="<?= htmlspecialchars($old[$f['name']]??'') ?>" <?= $f['required']?'required':'' ?>>
                <?php endif; ?>
                <?php if(!empty($errors[$f['name']])): ?>
                    <div class="text-danger small"><?= $errors[$f['name']] ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <button type="submit" name="submit_form" class="btn btn-success btn-animate">Submit</button>
        </form>
    </div>
    <?php } ?>

    <?php elseif($mode==='responses'): ?>
    <a href="index.php" class="btn btn-outline-primary btn-sm btn-animate mb-3"><i class="bi bi-house-door"></i> Back to Home</a>
    <?php
    if(!file_exists($responseFile) || !$responses=json_decode(file_get_contents($responseFile),true)){ 
        echo "<div class='alert alert-info'>No responses yet.</div>"; 
    }else{
        echo "<div class='main-card'><h3>Responses</h3><div class='table-responsive'><table class='table table-bordered table-striped'><thead><tr>";
        foreach(array_keys($responses[0]) as $col){ echo "<th>".htmlspecialchars($col)."</th>"; }
        echo "</tr></thead><tbody>";
        foreach($responses as $row){ echo "<tr>"; foreach($row as $val){ echo "<td>".(is_array($val)?implode(", ",$val):htmlspecialchars($val))."</td>"; } echo "</tr>"; }
        echo "</tbody></table></div></div>";
    }
    ?>
    <?php endif; ?>
    </div>
    </body>
    </html>
