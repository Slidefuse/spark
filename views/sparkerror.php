<html>	
	<head>
		<title><?= $this->data->errorName; ?> - Error</title>
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0-wip/css/bootstrap.min.css">
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0-wip/js/bootstrap.min.js"></script>
		<link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.min.css" rel="stylesheet">
	</head>

	<body>
		<div style="height: 64px;"></div>
		<div class="container">
			<div class="row">
				<div class="col-md-12" style="text-align: center;">
					<button type="button" class="btn btn-primary btn-lg" onclick="history.back(-1);">
						<i class="icon-chevron-left"></i> Back
					</button>

					<a href="<?= SparkPath::url(); ?>" type="button" class="btn btn-success btn-lg">
						<i class="icon-home"></i> Home
					</a>
				</div>
			</div>
			<div style="height: 32px;"></div>
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<div class="well">
						<p class="pull-right" style="font-size: 96px;">
							<i class="icon-frown"></i>
						</p>
						<h1><?= $this->data["errorName"]; ?></h1>
						<p>	
							<?= $this->data["errorMessage"]; ?>
						</p>
					</div>
				</div>
				<div class="col-md-3"></div>
			</div>
		</div>
	</body>
</html>
