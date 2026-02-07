<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asistencia QR</title>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
:root{
  --primary:#2563eb;
  --danger:#dc2626;
  --bg:#0f172a;
}

body{
  margin:0;
  font-family: system-ui, sans-serif;
  background:#f1f5f9;
}

header{
  background:var(--bg);
  color:white;
  padding:14px;
  text-align:center;
  font-size:18px;
}

/* CONTENEDOR */
.main{
  max-width:500px;
  margin:auto;
  padding:15px;
}

/* CUADRO CAMARA */
.camera-box{
  position:relative;
  width:100%;
  min-height:260px;
  border-radius:14px;
  overflow:hidden;
  background:#111827;
}

#reader{
  width:100%;
  height:100%;
}

/* ESTADO INACTIVO */
.camera-box.inactive::after{
  content:"";
  position:absolute;
  inset:0;
  background:rgba(0,0,0,.55);
  z-index:2;
}

.qr-placeholder{
  position:absolute;
  inset:0;
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:3;
  color:white;
  font-size:80px;
  opacity:.9;
}

/* MARCO QR ACTIVO */
.qr-frame{
  position:absolute;
  inset:20%;
  z-index:3;
  display:none;
}

.qr-frame span{
  position:absolute;
  width:30px;
  height:30px;
  border:4px solid #ffffff;
}

.qr-frame .tl{ top:0; left:0; border-right:none; border-bottom:none; }
.qr-frame .tr{ top:0; right:0; border-left:none; border-bottom:none; }
.qr-frame .bl{ bottom:0; left:0; border-right:none; border-top:none; }
.qr-frame .br{ bottom:0; right:0; border-left:none; border-top:none; }

/* BOTONES */
.controls{
  display:flex;
  gap:10px;
  justify-content:center;
  margin-top:15px;
}

button{
  padding:12px 16px;
  border:none;
  border-radius:10px;
  font-size:15px;
  cursor:pointer;
}

.primary{ background:var(--primary); color:white; }
.danger{ background:var(--danger); color:white; }
.gray{ background:#64748b; color:white; }

/* MODALES */
.overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.6);
  display:none;
  align-items:center;
  justify-content:center;
  z-index:1000;

  padding:14px; /* 👈 margen para teléfono */
  box-sizing:border-box;
}

.modal{
  background:white;
  width:92%;
  max-width:420px;
  border-radius:14px;
  padding:16px;
}

.modal img{
  width:100px;
  display:block;
  margin:10px auto;
}

.modal p{
  text-align:center;
}

.modal-buttons{
  display:flex;
  justify-content:space-between;
  gap:10px;
  margin-top:15px;
}

/* LISTA CURSOS */
.course-list{
  max-height:250px;
  overflow-y:auto;
}

.course-item{
  padding:12px;
  border-bottom:1px solid #e5e7eb;
  cursor:pointer;
}

.course-item.active{
  background:#dbeafe;
}
/* 🔧 ELIMINA DECORACIONES INTERNAS DE html5-qrcode */
#reader * {
  box-shadow: none !important;
  border: none !important;
  outline: none !important;
}

/* Evita marcos blancos internos */
#reader canvas,
#reader video {
  background: transparent !important;
}

/* Asegura que SOLO se vea tu marco azul */
.qr-frame {
  pointer-events: none;
}

</style>
</head>

<body>

<header>Registro de Asistencia</header>

<div class="main">

  <div class="camera-box inactive" id="cameraBox">
    <div id="reader"></div>

    <!-- ICONO QR -->
    <div class="qr-placeholder" id="qrPlaceholder">⌁</div>

    <!-- MARCO -->
    <div class="qr-frame" id="qrFrame">
      <span class="tl"></span>
      <span class="tr"></span>
      <span class="bl"></span>
      <span class="br"></span>
    </div>
  </div>

  <div class="controls">
    <button id="scanBtn" class="primary">Escanear</button>
    <button id="camBtn" class="gray">Elegir cámara</button>
  </div>

</div>

<!-- MODAL PRINCIPAL -->
<div class="overlay" id="modalMain">
  <div class="modal">
    <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png">
    <p id="qrText"></p>
    <p id="courseText"></p>

    <div class="modal-buttons">
      <button id="denyBtn" class="danger">No permitir</button>
      <button id="changeCourseBtn" class="gray">Ver otro curso</button>
      <button id="registerBtn" class="primary">Registrar</button>
    </div>
  </div>
</div>

<!-- MODAL CURSOS -->
<div class="overlay" id="modalCourses">
  <div class="modal">
    <h3>Seleccionar curso</h3>
    <div class="course-list" id="courseList"></div>
    <div class="modal-buttons">
      <button id="cancelCourse" class="danger">Cancelar</button>
      <button id="acceptCourse" class="primary">Aceptar</button>
    </div>
  </div>
</div>

<!-- MODAL CAMARAS -->
<div class="overlay" id="modalCameras">
  <div class="modal">
    <h3>Cámaras disponibles</h3>
    <div id="cameraList"></div>
  </div>
</div>

<script>
let scanner;
let scanning=false;
let selectedCamera=null;
let selectedCourseIndex=0;

let cursos=[
  "Matemática I",
  "Programación Básica",
  "Electrónica Digital"
];

const scanBtn=document.getElementById("scanBtn");
const cameraBox=document.getElementById("cameraBox");
const qrPlaceholder=document.getElementById("qrPlaceholder");
const qrFrame=document.getElementById("qrFrame");

scanBtn.onclick=async()=>{
  if(!scanning){
    scanner=new Html5Qrcode("reader");
    const cams=await Html5Qrcode.getCameras();
    selectedCamera=selectedCamera||cams[0].id;

    cameraBox.classList.remove("inactive");
    qrPlaceholder.style.display="none";
    qrFrame.style.display="block";

    scanner.start(selectedCamera,{fps:10,qrbox:250},onScan);
    scanBtn.textContent="Detener";
    scanning=true;
  }else{
    stopScanner();
  }
};

function stopScanner(){
  if(scanner){
    scanner.stop();
    scanning=false;
    scanBtn.textContent="Escanear";
    cameraBox.classList.add("inactive");
    qrPlaceholder.style.display="flex";
    qrFrame.style.display="none";
  }
}

function onScan(text){
  stopScanner();
  qrText.textContent="Código: "+text;
  updateCourseText();
  modalMain.style.display="flex";

  changeCourseBtn.style.display=cursos.length>1?"block":"none";
  registerBtn.style.display=cursos.length>0?"block":"none";
}

function updateCourseText(){
  courseText.textContent=cursos.length
    ? `Este alumno está ingresando al curso: "${cursos[0]}"`
    : "No hay cursos disponibles";
}

denyBtn.onclick=()=>modalMain.style.display="none";

registerBtn.onclick=()=>{
  alert("Registrado ✔");
  modalMain.style.display="none";
};

changeCourseBtn.onclick=()=>{
  modalCourses.style.display="flex";
  loadCourses();
};

function loadCourses(){
  courseList.innerHTML="";
  cursos.forEach((c,i)=>{
    const d=document.createElement("div");
    d.className="course-item"+(i===selectedCourseIndex?" active":"");
    d.textContent=c;
    d.onclick=()=>{
      document.querySelectorAll(".course-item").forEach(x=>x.classList.remove("active"));
      d.classList.add("active");
      selectedCourseIndex=i;
    };
    courseList.appendChild(d);
  });
}

acceptCourse.onclick=()=>{
  if(selectedCourseIndex!==0){
    [cursos[0],cursos[selectedCourseIndex]]=[cursos[selectedCourseIndex],cursos[0]];
  }
  modalCourses.style.display="none";
  updateCourseText();
};

cancelCourse.onclick=()=>modalCourses.style.display="none";

camBtn.onclick=async()=>{
  modalCameras.style.display="flex";
  cameraList.innerHTML="";
  const cams=await Html5Qrcode.getCameras();
  cams.forEach(c=>{
    const b=document.createElement("button");
    b.style.margin="5px";
    b.textContent=c.label||"Cámara";
    b.onclick=()=>{
      selectedCamera=c.id;
      modalCameras.style.display="none";
    };
    cameraList.appendChild(b);
  });
};
</script>

</body>
</html>
