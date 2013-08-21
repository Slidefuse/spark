<div class="navbar navbar-default" role="navigation">
	<div class="navbar-header">
		<a class="navbar-brand" href="<?= SparkPath::url(); ?>"><?= $SF->config["appTitle"]; ?></a>
	</div>

	<ul class="nav navbar-nav">
	<?php
		foreach ($this->data['elements'] as $k => $data) {
			switch ($data["type"]) {
				case "single":
					SparkPath::listItem($data["name"], $data["link"]);
					break;
				case "dropdown":
					echo '<li class="dropdown"'.((SparkPath::active($data["link"])) ? ' active' : '').'">';
					echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown">'.$data["name"].' <b class="caret"></b></a>';
					echo '<ul class="dropdown-menu">';
					foreach ($data["objects"] as $name => $link) {
						echo '<li><a href="'.SparkPath::url($link).'">'.$name.'</a></li>';
					}
					echo '</ul>';
					echo '</li>';
					break;
			}

		}
	?>
	</ul>
</div>