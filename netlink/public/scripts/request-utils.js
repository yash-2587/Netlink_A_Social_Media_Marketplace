export const makeRequest = (file, params = {}) => {
  // Create a copy of params to avoid modifying the original
  const requestParams = { ...params };
  
  // Extract CSRF token if it exists
  const csrfToken = requestParams.csrf_token;
  delete requestParams.csrf_token;

  // Build the URL with params and CSRF token
  const apiUrl = `backend_handler.php?file=${file}${
    Object.keys(requestParams).length > 0 
      ? `&params=${encodeURIComponent(JSON.stringify(requestParams))}` 
      : ''
  }${csrfToken ? `&csrf_token=${csrfToken}` : ''}`;

  return fetch(apiUrl)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text();
    })
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

export const fetchMessages = (receiverId, csrfToken) => {
  const file = "fetch_messages.php";
  const params = {
    receiver_id: receiverId
  };
  return makeRequest(file, { ...params, csrf_token: csrfToken });
};
<<<<<<< HEAD
=======
export const getItemsList = () => {
  const file = "get_items_list.php";
  return makeRequest(file, {});
};
>>>>>>> b580f41 (Initial commit)
