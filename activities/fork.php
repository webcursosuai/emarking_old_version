<?php
require_once (dirname(dirname ( dirname ( dirname ( __FILE__ ) ) ) ). '/config.php');
require_once ('generos.php');
GLOBAL $USER, $CFG;
$teacherroleid = 3;
$logged = false;
// Id of the exam to be deleted.
$forkid = required_param('id', PARAM_INT);
$editUrl = new moodle_url($CFG->wwwroot.'/mod/emarking/activities/edit.php', array('id' => $forkid));
require_login();
$PAGE->set_context(context_system::instance());
$url = new moodle_url($CFG->wwwroot.'/mod/emarking/activities/fork.php');
$PAGE->set_url($url);

if (isloggedin ()) {
	$logged = true;
	$courses = enrol_get_all_users_courses ( $USER->id );
	$countcourses = count ( $courses );
	foreach ( $courses as $course ) {
		$context = context_course::instance ( $course->id );
		$roles = get_user_roles ( $context, $USER->id, true );
		foreach ( $roles as $rol ) {
			if ($rol->roleid == $teacherroleid) {
				$asteachercourses [$course->id] = $course->fullname;
			}
		}
	}
}

$fork=$DB->get_record('emarking_activities',array('id'=>$forkid));
if($fork->userid != $USER->id){
	print_error('No tienes permiso para revisar esta actividad.');
	
}

$user_object = $DB->get_record('user', array('id'=>$fork->userid));

$rubric=$DB->get_records_sql("SELECT grl.id,
									 grc.id as grcid,
									 grl.score,
									 grl.definition, 
									 grc.description, 
									 grc.sortorder, 
									 gd.name
							  FROM {gradingform_rubric_levels} as grl,
	 							   {gradingform_rubric_criteria} as grc,
    							   {grading_definitions} as gd
							  WHERE gd.id=? AND grc.definitionid=gd.id AND grc.id=grl.criterionid
							  ORDER BY grcid, grl.id",
							  array($fork->rubricid));


foreach ($rubric as $data) {
	
	$table[$data->description][$data->definition]=$data->score;
}
$col=0;
foreach ($table as $calc) {
	
	$actualcol=sizeof($calc);
	if($col < $actualcol){
		$col=$actualcol;
	}
	
}
$row=sizeof($table);

$oaComplete=explode("-",$fork->learningobjectives);
$coursesOA="";
foreach($oaComplete as $oaPerCourse){

	$firstSplit=explode("[",$oaPerCourse);	
	$secondSplit=explode("]",$firstSplit[1]);
	$course=$firstSplit[0];
	
	$coursesOA .='<p>Curso: '.$firstSplit[0].'° básico</p>';
	$coursesOA .='<p>OAs: '.$secondSplit[0].'</p>';
}

?>
<!DOCTYPE html>
<html lang="en">
<!-- Head --> 
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="">
<title>Lorem Ipsum</title>
<!-- CSS Font, Bootstrap, style de la página y auto-complete  --> 
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="auto-complete.css">
<!-- Fin CSS -->
<!-- Css traidos desde google, no sé cuales realmete se usan  --> 
<link
	href='http://fonts.googleapis.com/css?family=Open+Sans:600italic,400,800,700,300'
	rel='stylesheet' type='text/css'>
<link	
	href='http://fonts.googleapis.com/css?family=BenchNine:300,400,700'
	rel='stylesheet' type='text/css'>
<link rel="stylesheet"
	href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300">
<link rel="stylesheet"
	href="https://cdn.rawgit.com/yahoo/pure-release/v0.6.0/pure-min.css">
<!-- Fin CSS de google -->
<!-- Importar  Scripts Javascript -->
<script src="js/modernizr.js"></script>

<!-- Fin Script Javascript -->
<!-- Scripts JQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
<script type="text/javascript" src="jquery-1.8.0.min.js"></script> 
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<!-- Script para filtro de genero -->

</head>

<!-- BODY -->
<body>
<!-- Header  -->

			<?php include 'header.php'; ?>
	

<!-- fIN DEL header -->
<!-- BUSCADOR -->
<section class="perfil">
	<div class="container">
		<div class="row">
			<h2></h2>
			<div class="col-md-3">
			<div class="panel panel-default">
					<div class="panel-body">
  				<center>
  				<a href="<?php echo $editUrl; ?>" class="btn btn-primary" role="button">Editar Actividad</a>
 				</center>
						
					</div>
			</div>
				<div class="panel panel-default">
					<div class="panel-body">
					<h3>Resumen</h3>
					
					<p>Título: <?php echo $fork->title; ?></p>
					<p>Descipción: <?php echo $fork->description;?></p>
					<?php echo $coursesOA; ?>
					<p>Propósito comunicativo: <?php echo $fork->comunicativepurpose; ?></p>
					<p>Género: <?php echo $fork->genre; ?></p>
					<p>Audiencia: <?php echo $fork->audience; ?></p>
					<p>Tiempo estimado: <?php echo $fork->estimatedtime; ?> minutos</p>
					<p>Creado por: <?php echo $user_object->firstname.' '.$user_object->lastname ?> </p>


					

					</div>
				</div>
			</div>
			<div class="col-md-9">
				<div class="panel panel-default">
					<div class="panel-body" >
					<h2 class="title"> <?php echo $fork->title ?> </h2>
					
 
  <ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#home">Instrucciones</a></li>
    <li><a data-toggle="tab" href="#menu1">Didáctica</a></li>
    <li><a data-toggle="tab" href="#menu2">Evaluación</a></li>
  </ul>

  <div class="tab-content">
	<div id="home" class="tab-pane fade in active">
		<h3 style="text-align: left;">Instrucciones para el estudiante</h3>
			
		<div class="panel panel-default">
			<div class="panel-body">	
				<?php 
				echo $fork->instructions;
				?>
			</div>
		</div>
	</div>


	<div id="menu1" class="tab-pane fade">
		<h3 style="text-align: left;">Didáctica</h3>
		
		<div class="panel panel-default">
			<div class="panel-body">
				<h4 style="text-align: left;">Sugerencias</h4>	
				<?php 
				echo $fork->teaching;
				?>

			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-body">
				<h4 style="text-align: left;">Recursos de la lengua</h4>	
				<?php 
				echo $fork->languageresources;
				?>

			</div>
		</div>

				
	</div>

	 <div id="menu2" class="tab-pane fade">
	 <h3 style="text-align: left;">Evaluación</h3>
			<table class="table table-bordered">
 					<thead>
     					<tr>
     				    <td></td>
     				    <?php 
     				    for ($i=1; $i <= $col; $i++) { 
     				    	echo "<th>Nivel $i</th>";
     				    }
     				    ?>
     				   
     					</tr>
   					</thead>
   					<tbody>

   				    	<?php 
   				    	foreach ($table as $key => $value) {
   				    		echo "<tr>";
   				    		   				    		
   				    		echo "<th>$key</th>";
   				    		foreach ($value as $level => $score) {
   				    			echo "<th>$level</th>";
   				    		}

   				    		echo "</tr>";
   				    	}

   				    	?>
   				    	

   				    </tbody>
   			</table>

					
	</div>
				</div>
 </div>
		</div>
		</div> 
	</div>
</section><!-- FIN BUSCADOR -->
<section >
	<div class="container">
		<div class="row">
			<h2></h2>
			<div class="panel panel-default">
				<div class="panel-body" >
					<h2 class="title">Social</h2>
					</div>
				</div>
			</div>
	</div>
</section>
</body>
<!-- footer starts here -->
<footer class="footer clearfix">
	<div class="container">
		<div class="row">
			<div class="col-xs-6 footer-para">
				<p>&copy; All right reserved</p>
			</div>
			<div class="col-xs-6 text-right">
				<a href=""><i class="fa fa-facebook"></i></a> <a href=""><i
					class="fa fa-twitter"></i></a>
			</div>
		</div>
	</div>
</footer>
