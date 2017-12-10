<?php
class NoGo
{
	private $side = 9;
	private $neighbourhood = [];
	public $map = [];
	public $moves = [];
	
	public function __construct()
	{
		for ($y = 0; $y < $this->side; ++$y) {
			for ($x = 0; $x < $this->side; ++$x) {
				$idx = $y * $this->side + $x + 1;
				$this->map[$x + 1][$y + 1] = $idx;
				$this->moves[$idx] = [$x + 1, $y + 1];
				$this->neighbourhood[$idx] = [];
				foreach ([[-1,0], [0,-1], [0,1], [1,0]] as $offset) {
					$yy = $y + $offset[0];
					$xx = $x + $offset[1];
					if ($yy >= 0 && $yy < $this->side && $xx >= 0 && $xx < $this->side) {
						$nidx = $yy * $this->side + $xx + 1;
						$this->neighbourhood[$idx][] = $nidx;
					}
				}
			}
		}
	}
	
	public function createInitialState()
	{
		$state = [
			'board' => [0],
			'liberties' => [0],
			'groupLiberties' => [0]
		];
		foreach ($this->neighbourhood as $nidx => $neighbours) {
			$state['board'][$nidx] = 0;
			$state['liberties'][$nidx] = count($neighbours);
			$state['groupLiberties'][$nidx] = count($neighbours);
		}
		return $state;
	}
	
	private function isBlack($idx, &$board)
	{
		return $board[$idx] > 0;
	}
	
	private function isWhite($idx, &$board)
	{
		return $board[$idx] < 0;
	}
	
	public function play($state, $idx, $color)
	{
		$state['board'][$idx] = $color === 'black' ? $idx : -$idx;
		$groups = [];
		foreach ($this->neighbourhood[$idx] as $nidx) {
			$state['liberties'][$nidx] -= 1;
			$nGroupIdx = $state['board'][$nidx] === 0 ? $nidx : abs($state['board'][$nidx]);
			$groups[] = $nGroupIdx;
		}
		foreach ($groups as $groupIdx) {
			$state['groupLiberties'][$groupIdx] -= 1;
		}
		$groupIdx = $idx;
		foreach ($this->neighbourhood[$idx] as $nidx) {
			$isTheSameColor = $color === 'black' 
				? $this->isBlack($nidx, $state['board'])
				: $this->isWhite($nidx, $state['board']);
			if ($isTheSameColor) {
				$nGroupIdx = abs($state['board'][$nidx]);
				if ($nGroupIdx < $groupIdx) {
					$pGroupIdx = $color === 'black' ? $groupIdx : -$groupIdx;
					$nGroupIdx = $color === 'black' ? $nGroupIdx : -$nGroupIdx;
					for ($i = 1, $count = count($state['board']); $i < $count; ++$i) {
						if ($state['board'][$i] === $pGroupIdx) {
							$state['board'][$i] = $nGroupIdx;
						}
					}
					$groupIdx = abs($nGroupIdx);
				} elseif ($nGroupIdx > $groupIdx) {
					$pGroupIdx = $color === 'black' ? $groupIdx : -$groupIdx;
					$nGroupIdx = $color === 'black' ? $nGroupIdx : -$nGroupIdx;
					for ($i = 1, $count = count($state['board']); $i < $count; ++$i) {
						if ($state['board'][$i] === $nGroupIdx) {
							$state['board'][$i] = $pGroupIdx;
						}
					}
				}
			}
		}
		$groupIdx = $color === 'black' ? $groupIdx : -$groupIdx;
		$liberties = array_fill(0, 82, 0);
		for ($i = 1, $count = count($state['board']); $i < $count; ++$i) {
			if ($state['board'][$i] === $groupIdx) {
				foreach ($this->neighbourhood[$i] as $nidx) {
					if ($state['board'][$nidx] === 0) {
						$liberties[$nidx] = 1;
					}
				}
			}
		}
		$state['groupLiberties'][abs($groupIdx)] = array_reduce($liberties, function ($carry, $item) {
			return $carry + $item;
		}, 0);
		$this->dump($state);
		return $state;
	}
	
	public function dump(&$state)
	{
		for ($y = 0; $y < $this->side; ++$y) {
			echo ' ' . $y . '|';
			for ($x = 0; $x < $this->side; ++$x) {
				$idx = $y * $this->side + $x + 1;
				echo ' ' . ($state['board'][$idx] > 0 ? 'B' : ($state['board'][$idx] == 0 ? '.' : 'W'));
			}
			echo '    ' . $y . '|';
			for ($x = 0; $x < $this->side; ++$x) {
				$idx = $y * $this->side + $x + 1;
				echo ' ' . str_pad($state['board'][$idx], 3, ' ', STR_PAD_LEFT);
			}
			echo '    ' . $y . '|';
			for ($x = 0; $x < $this->side; ++$x) {
				$idx = $y * $this->side + $x + 1;
				echo ' ' . $state['liberties'][$idx];
			}
			echo '    ' . $y . '|';
			for ($x = 0; $x < $this->side; ++$x) {
				$idx = $y * $this->side + $x + 1;
				echo ' ' . str_pad($state['groupLiberties'][$idx], 3, ' ', STR_PAD_LEFT);
			}
			echo PHP_EOL;
		}
		echo PHP_EOL;
	}
	
	public function moves(&$state, $color)
	{
		$moves = [];
		for ($idx = 1, $count = count($state['board']); $idx < $count; ++$idx) {
			if ($state['board'][$idx] === 0) {
				if ($state['liberties'][$idx] === 0) {
					$singleEye = true;
					foreach ($this->neighbourhood[$idx] as $nidx) {
						$nGroupIdx = abs($state['board'][$nidx]);
						$isTheSameColor = $color === 'black' 
							? $this->isBlack($nidx, $state['board'])
							: $this->isWhite($nidx, $state['board']);
						if ($isTheSameColor && $state['groupLiberties'][$nGroupIdx] > 1) {
							$singleEye = false;
						}
					}
					if ($singleEye) {
						continue;
					}
				}
				foreach ($this->neighbourhood[$idx] as $nidx) {
					$nGroupIdx = abs($state['board'][$nidx]);
					$isTheSameColor = $color === 'black' 
						? $this->isBlack($nidx, $state['board'])
						: $this->isWhite($nidx, $state['board']);
					if ($state['groupLiberties'][$nGroupIdx] === 1 && $isTheSameColor === false) {
						continue 2;
					}
				}
				$moves[] = $idx;
			}
		}
		return $moves;
	}
}

$nogo = new NoGo();

$ip = '127.0.0.1';
if (! empty($argv[2])) {
	$ip = trim($argv[2]);
}
$port = '6789';
if (! empty($argv[3])) {
	$port = (int)trim($argv[3]);
}
$name = 'php-test-client';
if (! empty($argv[4])) {
	$name = trim($argv[4]);
}

$socket = stream_socket_client('tcp://' . $ip . ':' . $port);
stream_set_timeout($socket, 86400);
if ($socket !== false) {
	fwrite($socket, "100 " . $name . "\r\n");
	while (true) {
		$result = fgets($socket);
		if ($result === false) {
			break;
		}
		$message = trim($result);
		if (strlen($message)) {
			echo $message . PHP_EOL;
			$result = explode(' ', trim($message));
			$code = $result[0];
			$parameters = array_slice($result, 1);
			if ($code == '200') {
				$state = $nogo->createInitialState();
				$color = $parameters[0];
				if ($parameters[0] === 'black') {
					$moves = $nogo->moves($state, $color);
					if (count($moves)) {
						$move = $moves[array_rand($moves)];
						$state = $nogo->play($state, $move, $color);
						fwrite($socket, "210 " . ($nogo->moves[$move][0]) . " " 
							. ($nogo->moves[$move][1]) . "\r\n");
					}
				}
			}
			else if ($code == '220') {
				$move = $nogo->map[(int) $parameters[0]][(int) $parameters[1]];
				$state = $nogo->play($state, $move, $color === 'black' ? 'white' : 'black');
				$moves = $nogo->moves($state, $color);
				if (count($moves)) {
					$move = $moves[array_rand($moves)];
					$state = $nogo->play($state, $move, $color);
					fwrite($socket, "210 " . ($nogo->moves[$move][0]) . " " 
							. ($nogo->moves[$move][1]) . "\r\n");
				}
			}
			else if (in_array($code, array('230', '231', '232'))) {
				echo "Zwyciężyłeś\n";
			}
			else if (in_array($code, array('240', '241'))) {
				echo "Zostałeś pokonany\n";
			}
			else if ($code == '999') {
				echo "Błąd: " . implode(' ', $parameters) . "\n";
			}
		}
	}
	fclose($socket);
}
