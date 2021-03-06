<?php
/**
 * Applies staff powers, like unblockableness, superhuman strength and
 * general awesomeness to select users.
 *
 * @file
 * @ingroup Extensions
 * @version 1.2
 * @date 28 January 2014
 * @author Łukasz Garczewski <tor@wikia-inc.com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 3.0 or later
 * @link https://www.mediawiki.org/wiki/Extension:StaffPowers Documentation
 */

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'StaffPowers',
	'version' => '1.2.1',
	'author' => array( 'Łukasz Garczewski', 'Jack Phoenix' ),
	'description' => 'Applies staff powers, like unblockableness, superhuman strength and general awesomeness to [[Special:ListUsers/staff|select users]]',
	'url' => 'https://www.mediawiki.org/wiki/Extension:StaffPowers',
);

$wgMessagesDirs['StaffPowers'] = __DIR__ . '/i18n';

// Power: unblockableness
$wgHooks['BlockIp'][] = 'efPowersMakeUnblockable';

$wgAvailableRights[] = 'unblockable';
$wgGroupPermissions['staff']['unblockable'] = true;

/**
 * @param Block $block The Block object about to be saved
 * @param User $user The user _doing_ the block (not the one being blocked)
 * @param array $reason Custom reason as to why blocking isn't possible
 * @return bool
 */
function efPowersMakeUnblockable( $block, $user, $reason ) {
	$blockedUser = User::newFromName( $block->getRedactedName() );

	if ( empty( $blockedUser ) ) {
		return true;
	}

	if ( User::isIP( $blockedUser ) ) {
		return true;
	}

	$userIsSteward = in_array( 'steward', $blockedUser->getEffectiveGroups() );
	if ( !$blockedUser->isAllowed( 'unblockable' ) && !$userIsSteward ) {
		return true;
	}

	// This exists for interoperability purposes with Wikia's StaffLog extension
	Hooks::run( 'BlockIpStaffPowersCancel', array( $block, $user ) );

	// Display a custom reason as to why blocking the specified user isn't
	// possible instead of the totally unhelpful, default core message
	$userIsStaff = in_array( 'staff', $blockedUser->getEffectiveGroups() );
	$blockerIsStaff = in_array( 'staff', $user->getEffectiveGroups() );

	// Don't allow staff to be blocked in any circumstances
	if ( $userIsStaff ) {
		$reason = array( 'staffpowers-ipblock-abort' );
	} elseif ( $userIsSteward && !$blockerIsStaff ) {
		// and also don't allow stewards to be blocked by non-staff, as per IRC
		// discussion on 19 January 2014
		$reason = array( 'staffpowers-steward-block-abort' );
	} elseif ( $userIsSteward && $blockerIsStaff ) {
		// This is a possible scenario - staff are allowed to block stewards.
		// We need to address this situation 'cause this function returns false
		// by default, so w/o this elseif loop, staff trying to block a steward
		// will bump into the default core hookaborted message and the block
		// will fail.
		return true;
	}

	return false;
}
