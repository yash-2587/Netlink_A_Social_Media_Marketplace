const makeRequest = (file, params) => {
  const apiUrl = `backend_handler.php?file=${file}&params=${encodeURIComponent(
    JSON.stringify(params)
  )}`;

  return fetch(apiUrl)
    .then((response) => response.text()) 
    .then((text) => {
      const jsonStartIndex = text.indexOf('{');
      const jsonEndIndex = text.lastIndexOf('}');
      if (jsonStartIndex === -1 || jsonEndIndex === -1) {
        throw new Error("Invalid JSON response from server.");
      }
      const jsonString = text.substring(jsonStartIndex, jsonEndIndex + 1);

      return JSON.parse(jsonString);
    })
    .then((data) => {
      return data.result; 
    })
    .catch((error) => {
      console.error("Fetch Error:", error);
      throw error; 
    });
};

export const validateCredentials = (username, password) => {
  const file = "validate_credentials.php";
  const params = { username: username, password: password };
  return makeRequest(file, params);
};

export const checkExists = (type, value) => {
  const file = "check_exists.php";
  const params = { type: type, value: value };
  return makeRequest(file, params);
};


export const getUsersList = () => {
  const file = "get_users_list.php";
  return makeRequest(file, {});
};

export const getUserId = () => {
  const file = "get_user_id.php";
  return makeRequest(file, {});
};


export const followUser = (followerId, followedId) => {
  const file = "follow_user.php";
  const params = { follower_id: followerId, followed_id: followedId };
  return makeRequest(file, params);
};

export const unfollowUser = (followerId, followedId) => {
  const file = "unfollow_user.php";
  const params = { follower_id: followerId, followed_id: followedId };
  return makeRequest(file, params);
};
