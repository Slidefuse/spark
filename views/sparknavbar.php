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
					echo "\n";
					break;
				case "dropdown":
					echo '<li class="dropdown'.((SparkPath::active($data["link"])) ? ' active' : '').'">'."\n";
					echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown">'.$data["name"].' <b class="caret"></b></a>'."\n";
					echo '<ul class="dropdown-menu">'."\n";
					foreach ($data["objects"] as $name => $link) {
						echo '<li><a href="'.SparkPath::url($link).'">'.$name.'</a></li>'."\n";
					}
					echo '</ul>'."\n";
					echo '</li>'."\n";
					break;
			}

		}
	?>
	</ul>
</div>