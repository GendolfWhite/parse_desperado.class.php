<?php

	class parse_desperado{
		var $_place_link = 'http://томск.десперадо.рф/category/photo/';
		var $_place = '';
		var $_curl_res = '';
		var $_album = [];
		var $_foto = [];

		public function __construct($place){
			$this->set('_place', $place);
			$this->set_place_link();
		}

		private function set($name, $value){
			$this->$name = $value;
		}

		private function set_place_link(){
			$this->set('_place_link', $this->_place_link.$this->_place.'/');
		}

		public function set_page($page){
			// В принципе нужна только для парсинга большого кол-ва альбомов..
			$this->set('_place_link', $this->_place_link.'/page/'.$page.'/');
		}

		private function curl($url = false, $options = [], $res = ''){
			if(!$url) $url = $this->_place_link;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $_options);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$res = @curl_exec($ch);
			curl_close($ch);
			$this->set('_curl_res', $res);
		}

		private function parse_place(){
			$this->curl();
			$code = explode("<div class=td-module-thumb>", explode('<div class="page-nav td-pb-padding-side">', explode("<div class=td-ss-main-content>", $this->_curl_res)[1])[0]);
			unset($code[0]);

			$albums = [];
			foreach($code as $key => $part){
				$code[$key] = explode("</div> <a ", $part)[0];
				$name = explode('title="', explode('"><img', $code[$key])[0])[1];
				$url = str_replace('https://xn--j1adfnc.xn--80ahbca0ddjg.xn--p1ai/', '', explode('<a href="', explode('" rel=bookmark', $code[$key])[0])[1]);
				$date = explode("/", str_replace('http://xn--j1adfnc.xn--80ahbca0ddjg.xn--p1ai/', '', $url));
				$date = $date[0].'-'.$date[1].'-'.$date[2];
				$preview = str_replace('https://xn--j1adfnc.xn--80ahbca0ddjg.xn--p1ai/', '', explode("324w, ", explode(' 533w"', $code[$key])[0])[1]);
				$albums[] = [
					'name' => $name,
					'url' => $url,
					'preview' => $preview,
					'date' => $date,
				];
			}

			$this->set('_album', $albums);
		}

		private function parse_album($url = false){
			$this->curl($url);
			$code = explode('<div class=wppa-tn-img-container', explode('<div class=wppa-clear style="clear:both;">', explode('<div id=wppa-thumb-area-1 ', $this->_curl_res)[1])[0]);
			$foto = [];
			foreach($code as $key => $part){
				$full = explode('<a href="', explode('" target=_self', $part)[0])[1];
				$small = explode('src="', explode('" alt', explode('<img ', $part)[1])[0])[1];
				$foto[] = [
					'full' => $full,
					'small' => $small
				];
			}
			unset($foto[0]);

			$this->set('_foto', $foto);
		}

		public function get($need, $url = false, $res = false){
			switch($need){
				case 'place':
					$this->parse_place();
					$res = $this->_album;
					break;

				case 'foto':
					$this->parse_album($url);
					$res = $this->_foto;
					break;
			}

			return ($res ? json_encode($res) : $res);
		}
	}

?>