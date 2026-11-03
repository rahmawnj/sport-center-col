<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
function output(int $status,array $data):never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function postJson(string $url,array $payload,?array $auth=null):array{
 $ch=curl_init($url);$opts=[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>35,CURLOPT_HTTPHEADER=>['Content-Type: application/json; charset=utf-8','Accept: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
 if($auth!==null){$opts[CURLOPT_HTTPAUTH]=CURLAUTH_BASIC;$opts[CURLOPT_USERPWD]=$auth[0].':'.$auth[1];}
 curl_setopt_array($ch,$opts);$body=curl_exec($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
 if($body===false)return ['transport_error'=>$error,'http_status'=>$status,'body'=>null];
 $decoded=json_decode((string)$body,true);return ['transport_error'=>null,'http_status'=>$status,'body'=>$decoded??(string)$body];
}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');output(405,['success'=>false,'message'=>'Gunakan POST.']);}
if(!function_exists('curl_init'))output(500,['success'=>false,'message'=>'PHP cURL belum aktif. Aktifkan extension=curl.']);
$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))output(400,['success'=>false,'message'=>'Body harus JSON.']);
$action=(string)($input['action']??'');$payload=$input['payload']??null;if(!is_array($payload))output(422,['success'=>false,'message'=>'Payload wajib berupa object JSON.']);
if($action==='face'){
 $base=rtrim(trim((string)($input['device_url']??'')),'/');$user=(string)($input['username']??'');$pass=(string)($input['password']??'');$parsed=parse_url($base);
 if(!is_array($parsed)||!in_array(strtolower((string)($parsed['scheme']??'')),['http','https'],true)||empty($parsed['host'])||isset($parsed['user'])||isset($parsed['pass'])||isset($parsed['query'])||isset($parsed['fragment']))output(422,['success'=>false,'message'=>'FaceRec URL tidak valid. Gunakan base URL HTTP(S).']);
 if($user===''||$pass==='')output(422,['success'=>false,'message'=>'Username dan password Basic Auth wajib diisi.']);
 if(($payload['operator']??'')!=='AddPersons'||(int)($payload['Total']??0)!==1||!isset($payload['DeviceID'])||!is_array($payload['Personinfo_0']??null)||empty($payload['Personinfo_0']['Name'])||empty($payload['Personinfo_0']['IdCard'])||empty($payload['Personinfo_0']['picinfo']))output(422,['success'=>false,'message'=>'Payload AddPersons tidak lengkap.']);
 $photo=(string)$payload['Personinfo_0']['picinfo'];if(!preg_match('#^data:image/jpeg;base64,#i',$photo))output(422,['success'=>false,'message'=>'Foto wajib JPEG data URL base64.']);
 $bytes=base64_decode(substr($photo,strpos($photo,',')+1),true);if($bytes===false||strlen($bytes)>10*1024*1024||@getimagesizefromstring($bytes)===false)output(422,['success'=>false,'message'=>'Foto JPEG tidak valid atau lebih dari 10 MB.']);
 $url=$base.'/action/AddPersons';$result=postJson($url,$payload,[$user,$pass]);
}elseif(in_array($action,['member','ticket','callback'],true)){
 $base=rtrim(trim((string)($input['laravel_url']??'')),'/');$parsed=parse_url($base);$host=strtolower((string)($parsed['host']??''));
 if(!is_array($parsed)||!in_array(strtolower((string)($parsed['scheme']??'')),['http','https'],true)||!in_array($host,['127.0.0.1','localhost','::1'],true)||isset($parsed['user'])||isset($parsed['pass'])||isset($parsed['query'])||isset($parsed['fragment']))output(422,['success'=>false,'message'=>'Base URL Laravel harus localhost / 127.0.0.1.']);
 if($action==='member'&&(empty($payload['device_id'])||empty($payload['member_code'])||($payload['device_type']??'')!=='member'))output(422,['success'=>false,'message'=>'device_id, device_type=member, dan member_code wajib diisi.']);
 if($action==='ticket'&&(empty($payload['device_id'])||empty($payload['scan_code'])||($payload['device_type']??'')!=='qrcode'))output(422,['success'=>false,'message'=>'device_id, device_type=qrcode, dan scan_code wajib diisi.']);
 if($action==='callback'&&(($payload['operator']??'')!=='VerifyPush'||!is_array($payload['info']??null)||empty($payload['info']['DeviceID'])||empty($payload['info']['IdCard'])))output(422,['success'=>false,'message'=>'Callback wajib berisi operator=VerifyPush, info.DeviceID, dan info.IdCard.']);
 $url=$base.'/api/device/access';$result=postJson($url,$payload);
}else output(422,['success'=>false,'message'=>'Jenis request tidak dikenal.']);
if($result['transport_error']!==null)output(502,['success'=>false,'message'=>'Gagal menghubungi endpoint: '.$result['transport_error'],'target_url'=>$url]);
output(200,['success'=>$result['http_status']>=200&&$result['http_status']<300,'target_url'=>$url,'target_http_status'=>$result['http_status'],'response'=>$result['body']]);
