<?php

	class parse_desperado{
		var $_place_link = 'http://xn--j1adfnc.xn--80ahbca0ddjg.xn--p1ai/category/photo/';
		var $_regexp = [
			'place' => '#<div class="td-block-span6">(.*?)</div> <!-- ./td-block-span6 -->#is',
			'album' => '#<div class="wppa-tn-img-container" style="(.*?)</div>#is',

			'album_url' => '<div class="td-module-thumb"><a href="(.*?)" rel="bookmark" title="',
			'album_name' => '" rel="bookmark" title="(.*?)"><img width="324" height="160" class="entry-thumb"',
			'album_preview' => '324w, (.*?) 533w',
			'album_date' => '" >(.*?)</time></span>',

			'foto_full' => '<a href="(.*?)" target="_self"',
			'foto_small' => 'src="(.*?)"  alt="',
		];
		var $_place = '';
		var $_place_content_code = '';
		var $_curl_res = '';
		var $_album = [];
		var $_album_content_code = '';
		var $_foto = [];

		public function __construct($place){
			$this->set('_place', $place);
			$this->set_place_link();
		}

		private function pre($res){
			echo '<textarea style="width:100%; min-height:750px;">';
			print_r($res);
			echo '</textarea>';
		}

		private function set($name, $value){
			$this->$name = $value;
		}

		private function set_place_link(){
			$this->set('_place_link', $this->_place_link.$this->_place.'/');
		}

		public function clean_uri($url){
			return str_replace(['http://xn--j1adfnc.xn--80ahbca0ddjg.xn--p1ai/','https://xn--j1adfnc.xn--80ahbca0ddjg.xn--p1ai/', 'http://томск.десперадо.рф/', 'httpы://томск.десперадо.рф/'], '', $url);
		}

		public function protocol($str, $protocol = 'https:', $work = 1){
			return ($work ? str_replace(['http:', 'https:'], $protocol, $str) : $str);
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

		private function preg($preg, $code){
			preg_match_all('#'.$preg.'#is', $code, $res, PREG_PATTERN_ORDER);
			return $res[1][0];
		}

		private function get_place_content_code(){
			preg_match_all($this->_regexp['place'], $this->_curl_res, $code, PREG_PATTERN_ORDER);
			$this->_place_content_code = $code[1];
		}

		private function get_album_content_code(){
			preg_match_all($this->_regexp['album'], $this->_curl_res, $code, PREG_PATTERN_ORDER);
			$this->_album_content_code = $code[0];
		}

		private function get_albums_data($code){
			return [
				'name' => $this->preg($this->_regexp['album_name'], $code),
				'url' => $this->preg($this->_regexp['album_url'], $code),
				'preview' => $this->preg($this->_regexp['album_preview'], $code),
				'date' => str_replace('/', '-', $this->preg($this->_regexp['album_date'], $code)),
			];
		}

		private function get_foto_data($code){
			return [
				'full' => $this->https($this->preg($this->_regexp['foto_full'], $code)),
				'small' => $this->https($this->preg($this->_regexp['foto_small'], $code)),
			];
		}

		private function parse_place(){
			$this->curl();
			$this->get_place_content_code();

			$albums = [];
			foreach($this->_place_content_code as $key => $part)
				$albums[] = $this->get_albums_data($part);

			$this->set('_album', $albums);
		}

		private function parse_album($url = false){
			$this->curl($url);
			$this->get_album_content_code();

			$foto = [];
			foreach($this->_album_content_code as $key => $part)
				$foto[] = $this->get_foto_data($part);

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

		public function https($str){
			return str_replace('http://', 'https://', $str);
		}
	}

?>