<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/user/fiche.php
 *       \brief      Tab of user card
 */

if (false === (@include '../../../main.inc.php')) { // From htdocs directory
    require '../../../../main.inc.php'; // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

if (is_file(DOL_DOCUMENT_ROOT.'/pos/backend/class/cashgroup.class.php')){
    require_once DOL_DOCUMENT_ROOT.'/pos/backend/class/cashgroup.class.php';
} else{
    require_once DOL_DOCUMENT_ROOT.'/custom/pos/backend/class/cashgroup.class.php';
}
if (is_file(DOL_DOCUMENT_ROOT.'/pos/backend/class/html.form.class.php')){
    require_once DOL_DOCUMENT_ROOT.'/pos/backend/class/html.form.class.php';
} else {
    require_once DOL_DOCUMENT_ROOT.'/custom/pos/backend/class/html.form.class.php';
} 
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->multicompany->enabled)) dol_include_once('/multicompany/class/actions_multicompany.class.php');

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);
$message='';

// Define value to know what current user can do on users
$canadduser=(! empty($user->admin) || $user->rights->user->user->creer);
$canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
$canedituser=(! empty($user->admin) || $user->rights->user->user->creer);
$candisableuser=(! empty($user->admin) || $user->rights->user->user->supprimer);
$canreadgroup=$canreaduser;
$caneditgroup=$canedituser;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadgroup=(! empty($user->admin) || $user->rights->user->group_advance->read);
    $caneditgroup=(! empty($user->admin) || $user->rights->user->group_advance->write);
}
// Define value to know what current user can do on properties of edited user
if ($id)
{
    // $user est le user qui edite, $id est l'id de l'utilisateur edite
    $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer));
    $caneditpassword=((($user->id == $id) && $user->rights->user->self->password)
    || (($user->id != $id) && $user->rights->user->user->password));
}

// Security check
$socid=0;
if ($user->socid > 0) $socid = $user->socid;
$feature2='user';
if ($user->id == $id) { $feature2=''; $canreaduser=1; } // A user can always read its own card
if (!$canreaduser) {
	$result = restrictedArea($user, 'user', $id, '&user', $feature2);
}
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("ldap");

$object = new User($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard'));



/**
 * Actions
 */

if ($action == 'confirm_disable' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $object->fetch($id);
        $object->setstatus(0);
        header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        exit;
    }
}
if ($action == 'confirm_enable' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $object->fetch($id);

        if (!empty($conf->file->main_limit_users))
        {
            $nb = $object->getNbOfUsers("active");
            if ($nb >= $conf->file->main_limit_users)
            {
                $message='<div class="error">'.$langs->trans("YourQuotaOfUsersIsReached").'</div>';
            }
        }

        if (! $message)
        {
            $object->setstatus(1);
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
    }
}

if ($action == 'confirm_delete' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $object = new User($db);
        $object->id=$id;
        $result = $object->delete();
        if ($result < 0)
        {
            $langs->load("errors");
            $message='<div class="error">'.$langs->trans("ErrorUserCannotBeDelete").'</div>';
        }
        else
        {
            header("Location: index.php");
            exit;
        }
    }
}

// Action ajout user
if ($action == 'add' && $canadduser)
{
    if (! $_POST["lastname"])
    {
        $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
        $action="create";       // Go back to create page
    }
    if (! $_POST["login"])
    {
        $message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
        $action="create";       // Go back to create page
    }

    if (! empty($conf->file->main_limit_users)) // If option to limit users is set
    {
        $nb = $object->getNbOfUsers("active");
        if ($nb >= $conf->file->main_limit_users)
        {
            $message='<div class="error">'.$langs->trans("YourQuotaOfUsersIsReached").'</div>';
            $action="create";       // Go back to create page
        }
    }

    if (! $message)
    {
        $object->lastname		= GETPOST("lastname");
        $object->firstname	    = GETPOST("firstname");
        $object->login		    = GETPOST("login");
        $object->admin		    = GETPOST("admin");
        $object->office_phone	= GETPOST("office_phone");
        $object->office_fax	    = GETPOST("office_fax");
        $object->user_mobile	= GETPOST("user_mobile");
        $object->skype    	  = GETPOST("skype");
        $object->email		    = GETPOST("email");
        $object->job			= GETPOST("job");
        $object->signature	    = GETPOST("signature");
        $object->accountancy_code = GETPOST("accountancy_code");
        $object->note			= GETPOST("note");
        $object->ldap_sid		= GETPOST("ldap_sid");

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);

        // If multicompany is off, admin users must all be on entity 0.
        if (! empty($conf->multicompany->enabled))
        {
        	if (! empty($_POST["superadmin"]))
        	{
        		$object->entity = 0;
        	}
        	else if ($conf->multicompany->transverse_mode)
        	{
        		$object->entity = 1; // all users in master entity
        	}
        	else
        	{
        		$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
        	}
        }
        else
        {
        	$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
        }

        $db->begin();

        $id = $object->create($user);
        if ($id > 0)
        {
            if (isset($_POST['password']) && trim($_POST['password']))
            {
                $object->setPassword($user,trim($_POST['password']));
            }

            $db->commit();

            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            $langs->load("errors");
            $db->rollback();
            if (is_array($object->errors) && count($object->errors)) $message='<div class="error">'.join('<br>',$langs->trans($object->errors)).'</div>';
            else $message='<div class="error">'.$langs->trans($object->error).'</div>';
            $action="create";       // Go back to create page
        }

    }
}

// Action ajout groupe utilisateur
if (($action == 'addgroup' || $action == 'removegroup') && $caneditfield)
{
    if ($group)
    {
        $editgroup = new CashGroup($db);
        $editgroup->fetch($group);
        $editgroup->oldcopy=dol_clone($editgroup);

        $object->fetch($id);
        if ($action == 'addgroup')    $editgroup->SetInGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity), $object->id);
        if ($action == 'removegroup') $editgroup->RemoveFromGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity),$object->id);

        if ($result > 0)
        {
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            $message.=$object->error;
        }
    }
}

if ($action == 'update' && ! $_POST["cancel"])
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    if ($caneditfield)	// Case we can edit all field
    {
        $error=0;

    	if (! $_POST["lastname"])
        {
            $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
            $action="edit";       // Go back to create page
            $error++;
        }
        if (! $_POST["login"])
        {
            $message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
            $action="edit";       // Go back to create page
            $error++;
        }

        if (! $error)
        {
            $object->fetch($id);

            // Test if new login
            if (GETPOST("login") && GETPOST("login") != $object->login)
            {
				dol_syslog("New login ".$object->login." is requested. We test it does not exists.");
				$tmpuser=new User($db);
				$result=$tmpuser->fetch(0, GETPOST("login"));
				if ($result > 0)
				{
					$message='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists").'</div>';
					$action="edit";       // Go back to create page
					$error++;
				}
            }
       }

       if (! $error)
       {
            $db->begin();

            $object->oldcopy=dol_clone($object);

            $object->lastname	= GETPOST("lastname");
            $object->firstname	= GETPOST("firstname");
            $object->login		= GETPOST("login");
            $object->pass		= GETPOST("password");
            $object->admin		= empty($user->admin)?0:GETPOST("admin"); // A user can only be set admin by an admin
            $object->office_phone=GETPOST("office_phone");
            $object->office_fax	= GETPOST("office_fax");
            $object->user_mobile= GETPOST("user_mobile");
            $object->skype    =GETPOST("skype");
            $object->email		= GETPOST("email");
            $object->job		= GETPOST("job");
            $object->signature	= GETPOST("signature");
			      $object->accountancy_code	= GETPOST("accountancy_code");
            $object->openid		= GETPOST("openid");
            $object->fk_user    = GETPOST("fk_user")>0?GETPOST("fk_user"):0;

            // Fill array 'array_options' with data from add form
        	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

            if (! empty($conf->multicompany->enabled))
            {
            	if (! empty($_POST["superadmin"]))
            	{
            		$object->entity = 0;
            	}
            	else if ($conf->multicompany->transverse_mode)
            	{
            		$object->entity = 1; // all users in master entity
            	}
            	else
            	{
            		$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            	}
            }
            else
            {
            	$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            }

            if (GETPOST('deletephoto')) $object->photo='';
            if (! empty($_FILES['photo']['name'])) $object->photo = dol_sanitizeFileName($_FILES['photo']['name']);

            $ret=$object->update($user);

            if ($ret < 0)
            {
            	$error++;
                if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
                {
                    $langs->load("errors");
                    $message.='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$object->login).'</div>';
                }
                else
              {
                    $message.='<div class="error">'.$object->error.'</div>';
                }
            }

            if (! $error && isset($_POST['contactid']))
            {
            	$contactid=GETPOST('contactid');

            	if ($contactid > 0)
            	{
	            	$contact=new Contact($db);
	            	$contact->fetch($contactid);

	            	$sql = "UPDATE ".MAIN_DB_PREFIX."user";
	            	$sql.= " SET fk_socpeople=".$contactid;
	            	if ($contact->socid) $sql.=", fk_societe=".$contact->socid;
	            	$sql.= " WHERE rowid=".$object->id;
            	}
            	else
            	{
            		$sql = "UPDATE ".MAIN_DB_PREFIX."user";
            		$sql.= " SET fk_socpeople=NULL, fk_societe=NULL";
            		$sql.= " WHERE rowid=".$object->id;
            	}
            	$resql=$db->query($sql);
            	dol_syslog("fiche::update sql=".$sql, LOG_DEBUG);
            	if (! $resql)
            	{
            		$error++;
            		$message.='<div class="error">'.$db->lasterror().'</div>';
            	}
            }

            if (! $error && ! count($object->errors))
            {
                if (GETPOST('deletephoto') && $object->photo)
                {
                    $fileimg=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1).'/logos/'.$object->photo;
                    $dirthumbs=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1).'/logos/thumbs';
                    dol_delete_file($fileimg);
                    dol_delete_dir_recursive($dirthumbs);
                }

                if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
                {
                    $dir= $conf->user->dir_output . '/' . get_exdir($object->id,2,0,1);

                    dol_mkdir($dir);

                    if (@is_dir($dir))
                    {
                        $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                        $result=dol_move_uploaded_file($_FILES['photo']['tmp_name'],$newfile,1,0,$_FILES['photo']['error']);

                        if (! $result > 0)
                        {
                            $message .= '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
                        }
                        else
                        {
                            // Create small thumbs for company (Ratio is near 16/9)
                            // Used on logon for example
                            $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                            // Create mini thumbs for company (Ratio is near 16/9)
                            // Used on menu or for setup page for example
                            $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
                        }
                    }
                }
            }

            if (! $error && ! count($object->errors))
            {
                $message.='<div class="ok">'.$langs->trans("UserModified").'</div>';
                $db->commit();

                $login=$_SESSION["dol_login"];
                if ($login && $login == $object->oldcopy->login && $object->oldcopy->login != $object->login)	// Current user has changed its login
                {
                	$_SESSION["dol_login"]=$object->login;	// Set new login to avoid disconnect at next page
                }
            }
            else
            {
                $db->rollback();
            }
        }
    }
    else if ($caneditpassword)	// Case we can edit only password
    {
        $object->fetch($id);

        $object->oldcopy=dol_clone($object);

        $ret=$object->setPassword($user,$_POST["password"]);
        if ($ret < 0)
        {
            $message.='<div class="error">'.$object->error.'</div>';
        }
    }
}

// Change password with a new generated one
if ((($action == 'confirm_password' && $confirm == 'yes')
|| ($action == 'confirm_passwordsend' && $confirm == 'yes')) && $caneditpassword)
{
    $object->fetch($id);

    $newpassword=$object->setPassword($user,'');
    if ($newpassword < 0)
    {
        // Echec
        $message = '<div class="error">'.$langs->trans("ErrorFailedToSetNewPassword").'</div>';
    }
    else
    {
        // Succes
        if ($action == 'confirm_passwordsend' && $confirm == 'yes')
        {
            if ($object->send_password($user,$newpassword) > 0)
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedAndSentTo",$object->email).'</div>';
                //$message.=$newpassword;
            }
            else
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
                $message.= '<div class="error">'.$object->error.'</div>';
            }
        }
        else
        {
            $message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
        }
    }
}

// Action initialisation donnees depuis record LDAP
if ($action == 'adduserldap')
{
    $selecteduser = $_POST['users'];

    $required_fields = array(
	$conf->global->LDAP_KEY_USERS,
    $conf->global->LDAP_FIELD_NAME,
    $conf->global->LDAP_FIELD_FIRSTNAME,
    $conf->global->LDAP_FIELD_LOGIN,
    $conf->global->LDAP_FIELD_LOGIN_SAMBA,
    $conf->global->LDAP_FIELD_PASSWORD,
    $conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
    $conf->global->LDAP_FIELD_PHONE,
    $conf->global->LDAP_FIELD_FAX,
    $conf->global->LDAP_FIELD_MOBILE,
    $conf->global->LDAP_FIELD_SKYPE,
    $conf->global->LDAP_FIELD_MAIL,
    $conf->global->LDAP_FIELD_TITLE,
	$conf->global->LDAP_FIELD_DESCRIPTION,
    $conf->global->LDAP_FIELD_SID);

    $ldap = new Ldap();
    $result = $ldap->connect_bind();
    if ($result >= 0)
    {
        // Remove from required_fields all entries not configured in LDAP (empty) and duplicated
        $required_fields=array_unique(array_values(array_filter($required_fields, "dol_validElement")));

        $ldapusers = $ldap->getRecords($selecteduser, $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields);
        //print_r($ldapusers);

        if (is_array($ldapusers))
        {
            foreach ($ldapusers as $key => $attribute)
            {
                $ldap_lastname		= $attribute[$conf->global->LDAP_FIELD_NAME];
                $ldap_firstname		= $attribute[$conf->global->LDAP_FIELD_FIRSTNAME];
                $ldap_login			= $attribute[$conf->global->LDAP_FIELD_LOGIN];
                $ldap_loginsmb		= $attribute[$conf->global->LDAP_FIELD_LOGIN_SAMBA];
                $ldap_pass			= $attribute[$conf->global->LDAP_FIELD_PASSWORD];
                $ldap_pass_crypted	= $attribute[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED];
                $ldap_phone			= $attribute[$conf->global->LDAP_FIELD_PHONE];
                $ldap_fax			= $attribute[$conf->global->LDAP_FIELD_FAX];
                $ldap_mobile		= $attribute[$conf->global->LDAP_FIELD_MOBILE];
                $ldap_skype			= $attribute[$conf->global->LDAP_FIELD_SKYPE];
                $ldap_mail			= $attribute[$conf->global->LDAP_FIELD_MAIL];
                $ldap_sid			= $attribute[$conf->global->LDAP_FIELD_SID];
            }
        }
    }
    else
    {
        $message='<div class="error">'.$ldap->error.'</div>';
    }
}



/*
 * View
 */
llxHeader('',$langs->trans("UserTerminalCard"));
$formpos = new FormCash($db);

//llxHeader('',$langs->trans("UserCard"));


$view = 1;
if($view)	
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Visu et edition                                                            */
    /*                                                                            */
    /* ************************************************************************** */

    if ($id > 0)
    {
        $object->fetch($id);
        if ($res < 0) { dol_print_error($db,$object->error); exit; }
        $res=$object->fetch_optionals($object->id,$extralabels);

        // Connexion ldap
        // pour recuperer passDoNotExpire et userChangePassNextLogon
        if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid))
        {
            $ldap = new Ldap();
            $result=$ldap->connect_bind();
            if ($result > 0)
            {
                $userSearchFilter = '('.$conf->global->LDAP_FILTER_CONNECTION.'('.$ldap->getUserIdentifier().'='.$object->login.'))';
                $entries = $ldap->fetch($object->login,$userSearchFilter);
                if (! $entries)
                {
                    $message .= $ldap->error;
                }

                $passDoNotExpire = 0;
                $userChangePassNextLogon = 0;
                $userDisabled = 0;
                $statutUACF = '';

                //On verifie les options du compte
                if (count($ldap->uacf) > 0)
                {
                    foreach ($ldap->uacf as $key => $statut)
                    {
                        if ($key == 65536)
                        {
                            $passDoNotExpire = 1;
                            $statutUACF = $statut;
                        }
                    }
                }
                else
                {
                    $userDisabled = 1;
                    $statutUACF = "ACCOUNTDISABLE";
                }

                if ($ldap->pwdlastset == 0)
                {
                    $userChangePassNextLogon = 1;
                }
            }
        }

        // Show tabs
        $head = user_prepare_head($object);

        $title = $langs->trans("User");
        dol_fiche_head($head, 'user', $title, 0, 'user');

        /*
         * Confirmation reinitialisation mot de passe
         */
        if ($action == 'password')
        {
            print $formpos->formconfirm("fiche.php?id=$object->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$object->login),"confirm_password", '', 0, 1);
        }

        /*
         * Confirmation envoi mot de passe
         */
        if ($action == 'passwordsend')
        {
            print $formpos->formconfirm("fiche.php?id=$object->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$object->login),"confirm_passwordsend", '', 0, 1);
        }

        /*
         * Confirmation desactivation
         */
        if ($action == 'disable')
        {
            print $formpos->formconfirm("fiche.php?id=$object->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$object->login),"confirm_disable", '', 0, 1);
        }

        /*
         * Confirmation activation
         */
        if ($action == 'enable')
        {
            print $formpos->formconfirm("fiche.php?id=$object->id",$langs->trans("EnableAUser"),$langs->trans("ConfirmEnableUser",$object->login),"confirm_enable", '', 0, 1);
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            print $formpos->formconfirm("fiche.php?id=$object->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$object->login),"confirm_delete", '', 0, 1);
        }

        dol_htmloutput_mesg($message);

        /*
         * Fiche en mode visu
         */
        if ($action != 'edit')
        {
            $rowspan=16;

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $formpos->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
            print '</td>';
            print '</tr>'."\n";

            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
            if (! empty($conf->societe->enabled)) $rowspan++;
            if (! empty($conf->adherent->enabled)) $rowspan++;

            // Lastname
            print '<tr><td valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td>'.$object->lastname.'</td>';

            // Photo
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            print $formpos->showphoto('userphoto',$object,100);
            print '</td>';

            print '</tr>'."\n";

            // Firstname
            print '<tr><td valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td>'.$object->firstname.'</td>';
            print '</tr>'."\n";

            // Position/Job
            print '<tr><td valign="top">'.$langs->trans("PostOrFunction").'</td>';
            print '<td>'.$object->job.'</td>';
            print '</tr>'."\n";

            // Login
            print '<tr><td valign="top">'.$langs->trans("Login").'</td>';
            if (! empty($object->ldap_sid) && $object->statut==0)
            {
                print '<td class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
            }
            else
            {
                print '<td>'.$object->login.'</td>';
            }
            print '</tr>'."\n";

            // Password
            print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
            if (! empty($object->ldap_sid))
            {
                if ($passDoNotExpire)
                {
                    print '<td>'.$langs->trans("LdapUacf_".$statutUACF).'</td>';
                }
                else if($userChangePassNextLogon)
                {
                    print '<td class="warning">'.$langs->trans("UserMustChangePassNextLogon",$ldap->domainFQDN).'</td>';
                }
                else if($userDisabled)
                {
                    print '<td class="warning">'.$langs->trans("LdapUacf_".$statutUACF,$ldap->domainFQDN).'</td>';
                }
                else
                {
                    print '<td>'.$langs->trans("DomainPassword").'</td>';
                }
            }
            else
            {
                print '<td>';
                if ($object->pass) print preg_replace('/./i','*',$object->pass);
                else
                {
                    if ($user->admin) print $langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
                    else print $langs->trans("Hidden");
                }
                print "</td>";
            }
            print '</tr>'."\n";

            // Administrator
            print '<tr><td valign="top">'.$langs->trans("Administrator").'</td><td>';
            if (! empty($conf->multicompany->enabled) && $object->admin && ! $object->entity)
            {
                print $formpos->textwithpicto(yn($object->admin),$langs->trans("SuperAdministratorDesc"),1,"superadmin");
            }
            else if ($object->admin)
            {
                print $formpos->textwithpicto(yn($object->admin),$langs->trans("AdministratorDesc"),1,"admin");
            }
            else
            {
                print yn($object->admin);
            }
            print '</td></tr>'."\n";

            // Type
            print '<tr><td valign="top">'.$langs->trans("Type").'</td><td>';
            $type=$langs->trans("Internal");
            if ($object->socid) $type=$langs->trans("External");
            print $formpos->textwithpicto($type,$langs->trans("InternalExternalDesc"));
            if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
            print '</td></tr>'."\n";

            // Ldap sid
            if ($object->ldap_sid)
            {
            	print '<tr><td valign="top">'.$langs->trans("Type").'</td><td>';
            	print $langs->trans("DomainUser",$ldap->domainFQDN);
            	print '</td></tr>'."\n";
            }

            // Tel pro
            print '<tr><td valign="top">'.$langs->trans("PhonePro").'</td>';
            print '<td>'.dol_print_phone($object->office_phone,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Tel mobile
            print '<tr><td valign="top">'.$langs->trans("PhoneMobile").'</td>';
            print '<td>'.dol_print_phone($object->user_mobile,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Fax
            print '<tr><td valign="top">'.$langs->trans("Fax").'</td>';
            print '<td>'.dol_print_phone($object->office_fax,'',0,0,1).'</td>';
            print '</tr>'."\n";
            
            // Skype
            if (! empty($conf->skype->enabled))
            {
                print '<tr><td valign="top">'.$langs->trans("Skype").'</td>';
                print '<td>'.dol_print_skype($object->skype,0,0,1).'</td>';
                print "</tr>\n";
            }

            // EMail
            print '<tr><td valign="top">'.$langs->trans("EMail").'</td>';
            print '<td>'.dol_print_email($object->email,0,0,1).'</td>';
            print "</tr>\n";

            // Signature
            print '<tr><td valign="top">'.$langs->trans('Signature').'</td><td>';
            print dol_htmlentitiesbr($object->signature);
            print "</td></tr>\n";

            // Hierarchy
            print '<tr><td valign="top">'.$langs->trans("HierarchicalResponsible").'</td>';
            print '<td>';
            if (empty($object->fk_user)) print $langs->trans("None");
            else {
            	$huser=new User($db);
            	$huser->fetch($object->fk_user);
            	print $huser->getNomUrl(1);
            }
            print '</td>';
            print "</tr>\n";

			// Accountancy code
			if (! empty($conf->global->USER_ENABLE_ACCOUNTANCY_CODE))	// For the moment field is not used so must not appeared.
			{
				$rowspan++;
            	print '<tr><td valign="top">'.$langs->trans("AccountancyCode").'</td>';
            	print '<td>'.$object->accountancy_code.'</td>';
			}

            // Status
            print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
            print '<td>';
            print $object->getLibStatut(4);
            print '</td>';
            print '</tr>'."\n";

            print '<tr><td valign="top">'.$langs->trans("LastConnexion").'</td>';
            print '<td>'.dol_print_date($object->datelastlogin,"dayhour").'</td>';
            print "</tr>\n";

            print '<tr><td valign="top">'.$langs->trans("PreviousConnexion").'</td>';
            print '<td>'.dol_print_date($object->datepreviouslogin,"dayhour").'</td>';
            print "</tr>\n";

            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
            {
                print '<tr><td valign="top">'.$langs->trans("OpenIDURL").'</td>';
                print '<td>'.$object->openid.'</td>';
                print "</tr>\n";
            }

            // Company / Contact
            if (! empty($conf->societe->enabled))
            {
                print '<tr><td valign="top">'.$langs->trans("LinkToCompanyContact").'</td>';
                print '<td>';
                if (isset($object->socid) && $object->socid > 0)
                {
                    $societe = new Societe($db);
                    $societe->fetch($object->socid);
                    print $societe->getNomUrl(1,'');
                }
                else
                {
                    print $langs->trans("ThisUserIsNot");
                }
                if (! empty($object->contact_id))
                {
                    $contact = new Contact($db);
                    $contact->fetch($object->contact_id);
                    if ($object->socid > 0) print ' / ';
                    else print '<br>';
                    print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
                }
                print '</td>';
                print '</tr>'."\n";
            }

            // Module Adherent
            if (! empty($conf->adherent->enabled))
            {
                $langs->load("members");
                print '<tr><td valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
                print '<td>';
                if ($object->fk_member)
                {
                    $adh=new Adherent($db);
                    $adh->fetch($object->fk_member);
                    $adh->ref=$adh->getFullname($langs);	// Force to show login instead of id
                    print $adh->getNomUrl(1);
                }
                else
                {
                    print $langs->trans("UserNotLinkedToMember");
                }
                print '</td>';
                print '</tr>'."\n";
            }

            // Multicompany
            if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            {
            	print '<tr><td valign="top">'.$langs->trans("Entity").'</td><td width="75%" class="valeur">';
            	if ($object->admin && ! $object->entity)
            	{
            		print $langs->trans("AllEntities");
            	}
            	else
            	{
            		$mc->getInfo($object->entity);
            		print $mc->label;
            	}
            	print "</td></tr>\n";
            }

          	// Other attributes
			$parameters=array('colspan' => ' colspan="2"');
			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
			if (empty($reshook) && ! empty($extrafields->attribute_label))
			{
				print $object->showOptionals($extrafields);
			}
			
			print "</table>\n";

            print "</div>\n";


            /*
             * Buttons actions
             */
/*
            print '<div class="tabsAction">';
            // Activer
            if ($user->id <> $id && $candisableuser && $object->statut == 0 &&
            ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=enable">'.$langs->trans("Reactivate").'</a></div>';
            }
            // Desactiver
            if ($user->id <> $id && $candisableuser && $object->statut == 1 &&
            ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=disable&amp;id='.$object->id.'">'.$langs->trans("DisableUser").'</a></div>';
            }
            // Delete
            if ($user->id <> $id && $candisableuser &&
            ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
            	if ($user->admin || ! $object->admin) // If user edited is admin, delete is possible on for an admin
            	{
                	print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("DeleteUser").'</a></div>';
            	}
            	else
            	{
            		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("MustBeAdminToDeleteOtherAdmin")).'">'.$langs->trans("DeleteUser").'</a></div>';
            	}
            }

            print "</div>\n";
            print "<br>\n";
*/


            /*
             * Liste des groupes dans lequel est l'utilisateur
             */

            if ($canreadgroup)
            {
                print_fiche_titre($langs->trans("ListOfTerminalsForUser"),'','');

                // On selectionne les groupes auquel fait parti le user
                $exclude = array();

                $usergroup=new CashGroup($db);
                $groupslist = $usergroup->listGroupsForUser($object->id);

                if (! empty($groupslist))
                {
                    if (! (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)))
                    {
                        foreach($groupslist as $groupforuser)
                        {
                            $exclude[]=$groupforuser->id;
                        }
                    }
                }

                if ($caneditgroup)
                {
                    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">'."\n";
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
                    print '<input type="hidden" name="action" value="addgroup" />';
                    print '<table class="noborder" width="100%">'."\n";
                    print '<tr class="liste_titre"><th class="liste_titre" width="25%">'.$langs->trans("GroupsToAdd").'</th>'."\n";
                    print '<th>';
                    print $formpos->select_dolgroups('', 'group', 1, $exclude, 0, '', '', $object->entity);
                    print ' &nbsp; ';
                    // Multicompany
                    if (! empty($conf->multicompany->enabled))
                    {
                        if ($conf->entity == 1 && $conf->multicompany->transverse_mode)
                        {
                            print '</td><td valign="top">'.$langs->trans("Entity").'</td>';
                            print "<td>".$mc->select_entities($conf->entity);
                        }
                        else
                        {
                            print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
                        }
                    }
                    else
                    {
                    	print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
                    }
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
                    print '</th></tr>'."\n";
                    print '</table></form>'."\n";

                    print '<br>';
                }

                /*
                 * Groups assigned to user
                 */
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td class="liste_titre" width="25%">'.$langs->trans("Groups").'</td>';
                if(! empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                {
                	print '<td class="liste_titre" width="25%">'.$langs->trans("Entity").'</td>';
                }
                print "<td>&nbsp;</td></tr>\n";

                if (! empty($groupslist))
                {
                    $var=true;

                    foreach($groupslist as $group)
                    {
                        $var=!$var;

                        print "<tr ".$bc[$var].">";
                        print '<td>';
                        if ($caneditgroup)
                        {
                            print '<a href="'.DOL_URL_ROOT.'/pos/backend/terminal/fiche.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->name.'</a>';
                        }
                        else
                        {
                            print img_object($langs->trans("ShowGroup"),"group").' '.$group->name;
                        }
                        print '</td>';
                        if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                        {
                        	print '<td class="valeur">';
                        	if (! empty($group->usergroup_entity))
                        	{
                        		$nb=0;
                        		foreach($group->usergroup_entity as $group_entity)
                        		{
                        			$mc->getInfo($group_entity);
                        			print ($nb > 0 ? ', ' : '').$mc->label;
                        			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'&amp;entity='.$group_entity.'">';
                        			print img_delete($langs->trans("RemoveFromGroup"));
                        			print '</a>';
                        			$nb++;
                        		}
                        	}
                        }
                        print '<td align="right">';
                        if ($caneditgroup && empty($conf->multicompany->transverse_mode))
                        {
                            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'">';
                            print img_delete($langs->trans("RemoveFromGroup"));
                            print '</a>';
                        }
                        else
                        {
                            print "&nbsp;";
                        }
                        print "</td></tr>\n";
                    }
                }
                else
                {
                    print '<tr '.$bc[false].'><td colspan="3">'.$langs->trans("None").'</td></tr>';
                }
                if($_REQUEST['permiso'] && $_REQUEST['pmixtos'] && $_REQUEST['pcproducto']){
                	$sqlab="UPDATE ".MAIN_DB_PREFIX."pos_cashgroup_cash SET 
                			permiso=".$_REQUEST['permiso'].", pmixtos=".$_REQUEST['pmixtos'].",
                					pcproducto=".$_REQUEST['pcproducto']." WHERE fk_user=".$id;
                	dol_syslog('QUERY PERMISO: '.$sqlab);
                	$resab=$db->query($sqlab);
                }
                $sqla="SELECT permiso,pmixtos,pcproducto FROM ".MAIN_DB_PREFIX."pos_cashgroup_cash WHERE fk_user=".$id;
                dol_syslog('QUERY PERMISO: '.$sqla);
                $resa=$db->query($sqla);
                $resb=$db->fetch_object($resa);
                $resc=$db->num_rows($resa);
                dol_syslog('PERMISO: '.$resb->permiso);
                $a='';
                $b='';
                $c='';
                $d='';
                $e='';
                $f='';
                if($resb->permiso==1){
                	$a='SELECTED';
                	$b='';
                }else{
                	if($resb->permiso==2){
                		$a='';
                		$b='SELECTED';
                	}
                }
                if($resb->pmixtos==1){
                	$c='SELECTED';
                	$d='';
                }else{
                	if($resb->pmixtos==2){
                		$c='';
                		$d='SELECTED';
                	}
                }
                if($resb->pcproducto==1){
                	$e='SELECTED';
                	$f='';
                }else{
                	if($resb->pcproducto==2){
                		$e='';
                		$f='SELECTED';
                	}
                }
                if($resc>0){
                print "<form method='GET' >";
                print "<input type='hidden' name='id' value='".$id."'>";
                print "<tr>
					 <td>Permiso Descuento en Caja</td><td>
					 <select name='permiso'>";
                print "<option value='1' ".$a." >SI</option>";
                print "<option value='2' ".$b." >NO</option>";
                print "</select></td>
					</tr>";
                print "<tr>
					 <td>Permitir Pagos Mixtos</td><td>
					 <select name='pmixtos'>";
                print "<option value='1' ".$c." >SI</option>";
                print "<option value='2' ".$d." >NO</option>";
                print "</select></td>
					</tr>";
                print "<tr>
					 <td>Permitir Crear Productos</td><td>
					 <select name='pcproducto'>";
                print "<option value='1' ".$e." >SI</option>";
                print "<option value='2' ".$f." >NO</option>";
                print "</select></td>
					</tr>";
                print "<tr><td colspan='2' align='center'><input type='submit' value='Guardar Permisos'></td></tr>";
                print "</form>";
                }
                print "</table>";
                print "<br>";
            }
        }
		if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid)) $ldap->close;
    }
}


llxFooter();
$db->close();
?>