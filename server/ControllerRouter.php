<?php

//!!!
//!!!  do not edit, generated by script/build_route.sh
//!!!

namespace site;

use site\ControllerFactory;

/**
 * Description of ControllerRouter
 *
 * @author ikki
 */
class ControllerRouter extends ControllerFactory
{

   protected static $_route = [
      'activity'           =>  'site\\controller\\activity\\ActivityCtrler',
      'ad'                 =>  'site\\controller\\ad\\ADCtrler',
      'adm'                =>  'site\\controller\\adm\\AdmCtrler',
      'adm/ad'             =>  'site\\controller\\adm\\AdCtrler',
      'adm/cache'          =>  'site\\controller\\adm\\CacheCtrler',
      'bug'                =>  'site\\controller\\bug\\BugCtrler',
      'captcha'            =>  'site\\controller\\captcha\\CaptchaCtrler',
      'comment'            =>  'site\\controller\\comment\\CommentCtrler',
      'comment/delete'     =>  'site\\controller\\comment\\DeleteCtrler',
      'comment/edit'       =>  'site\\controller\\comment\\EditCtrler',
      'file/ajax'          =>  'site\\controller\\file\\AJAXCtrler',
      'forum'              =>  'site\\controller\\forum\\ForumCtrler',
      'forum/ajax'         =>  'site\\controller\\forum\\AJAXCtrler',
      'forum/node'         =>  'site\\controller\\forum\\NodeCtrler',
      'help'               =>  'site\\controller\\help\\HelpCtrler',
      'home'               =>  'site\\controller\\home\\HomeCtrler',
      'home/ajax'          =>  'site\\controller\\home\\AJAXCtrler',
      'iostat'             =>  'site\\controller\\iostat\\IOStatCtrler',
      'lottery'            =>  'site\\controller\\lottery\\LotteryCtrler',
      'lottery/prize'      =>  'site\\controller\\lottery\\PrizeCtrler',
      'lottery/rank'       =>  'site\\controller\\lottery\\RankCtrler',
      'lottery/start'      =>  'site\\controller\\lottery\\StartCtrler',
      'lottery/try'        =>  'site\\controller\\lottery\\TryCtrler',
      'node'               =>  'site\\controller\\node\\NodeCtrler',
      'node/activity'      =>  'site\\controller\\node\\ActivityCtrler',
      'node/ajax'          =>  'site\\controller\\node\\AJAXCtrler',
      'node/bookmark'      =>  'site\\controller\\node\\BookmarkCtrler',
      'node/comment'       =>  'site\\controller\\node\\CommentCtrler',
      'node/delete'        =>  'site\\controller\\node\\DeleteCtrler',
      'node/edit'          =>  'site\\controller\\node\\EditCtrler',
      'node/tag'           =>  'site\\controller\\node\\TagCtrler',
      'password/change'    =>  'site\\controller\\password\\ChangeCtrler',
      'password/forget'    =>  'site\\controller\\password\\ForgetCtrler',
      'password/reset'     =>  'site\\controller\\password\\ResetCtrler',
      'phpinfo'            =>  'site\\controller\\phpinfo\\PHPInfoCtrler',
      'pm'                 =>  'site\\controller\\pm\\PMCtrler',
      'pm/delete'          =>  'site\\controller\\pm\\DeleteCtrler',
      'pm/mailbox'         =>  'site\\controller\\pm\\MailBoxCtrler',
      'pm/reply'           =>  'site\\controller\\pm\\ReplyCtrler',
      'pm/send'            =>  'site\\controller\\pm\\SendCtrler',
      'schools'            =>  'site\\controller\\schools\\SchoolsCtrler',
      'search'             =>  'site\\controller\\search\\SearchCtrler',
      'sendmail'           =>  'site\\controller\\sendmail\\SendMailCtrler',
      'single'             =>  'site\\controller\\single\\SingleCtrler',
      'single/activities'  =>  'site\\controller\\single\\ActivitiesCtrler',
      'single/ajax'        =>  'site\\controller\\single\\AJAXCtrler',
      'single/attendee'    =>  'site\\controller\\single\\AttendeeCtrler',
      'single/checkin'     =>  'site\\controller\\single\\CheckinCtrler',
      'single/info'        =>  'site\\controller\\single\\InfoCtrler',
      'single/list'        =>  'site\\controller\\single\\ListCtrler',
      'single/login'       =>  'site\\controller\\single\\LoginCtrler',
      'single/logout'      =>  'site\\controller\\single\\LogoutCtrler',
      'term'               =>  'site\\controller\\term\\TermCtrler',
      'user'               =>  'site\\controller\\user\\UserCtrler',
      'user/activate'      =>  'site\\controller\\user\\ActivateCtrler',
      'user/bookmark'      =>  'site\\controller\\user\\BookmarkCtrler',
      'user/delete'        =>  'site\\controller\\user\\DeleteCtrler',
      'user/edit'          =>  'site\\controller\\user\\EditCtrler',
      'user/login'         =>  'site\\controller\\user\\LoginCtrler',
      'user/logout'        =>  'site\\controller\\user\\LogoutCtrler',
      'user/register'      =>  'site\\controller\\user\\RegisterCtrler',
      'user/su'            =>  'site\\controller\\user\\SUCtrler',
      'user/username'      =>  'site\\controller\\user\\UsernameCtrler',
      'weather'            =>  'site\\controller\\weather\\WeatherCtrler',
      'wedding'            =>  'site\\controller\\wedding\\WeddingCtrler',
      'wedding/add'        =>  'site\\controller\\wedding\\AddCtrler',
      'wedding/checkin'    =>  'site\\controller\\wedding\\CheckinCtrler',
      'wedding/edit'       =>  'site\\controller\\wedding\\EditCtrler',
      'wedding/gift'       =>  'site\\controller\\wedding\\GiftCtrler',
      'wedding/join'       =>  'site\\controller\\wedding\\JoinCtrler',
      'wedding/listall'    =>  'site\\controller\\wedding\\ListAllCtrler',
      'wedding/login'      =>  'site\\controller\\wedding\\LoginCtrler',
      'wedding/logout'     =>  'site\\controller\\wedding\\LogoutCtrler',
      'yp'                 =>  'site\\controller\\yp\\YPCtrler',
      'yp/ajax'            =>  'site\\controller\\yp\\AJAXCtrler',
      'yp/join'            =>  'site\\controller\\yp\\JoinCtrler',
      'yp/node'            =>  'site\\controller\\yp\\NodeCtrler',
   ];

}

//__END_OF_FILE__
