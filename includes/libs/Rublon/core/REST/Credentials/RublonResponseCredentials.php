<?php

/**
 * REST response: Credentials.
 * 
 * This class handles a RublonRequest response used
 * for retrieving Rublon user's credentials.
 * 
 * @author Rublon Developers
 * @version 2013-07-05
 */
class RublonResponseCredentials extends RublonResponse {


	/**
	 * Get user data.
	 * 
	 * Returns Rublon user's data as an array.
	 *
	 * @return array
	 */
	public function getUserData() {
		return $this->data['userData'];
	}
	
	
	/**
	 * Get Rublon user's profile ID.
	 * 
	 * Returns Rublon user's profileId.
	 * 
	 * @return int
	 */
	public function getProfileId() {
		$userData = $this->getUserData();
		return $userData['profileId'];
	}
	
	
	/**
	 * Check if given profile ID is the same like in the response.
	 * 
	 * Performs a check if the given profileId is the same
	 * as the profileId of the user authenticated by Rublon.
	 * 
	 * @param int $profileId Rublon user's profileId
	 * @return boolean
	 */
	public function checkProfileId($profileId) {
		return ($profileId == $this->getProfileId());
	}


}
