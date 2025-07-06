import { Pressable, Text, StyleSheet } from 'react-native';

export default function PrimaryButton({ title, onPress }) {
  return (
    <Pressable
      style={({ pressed }) => [
        styles.buttonSuccess,
        pressed && styles.buttonSuccessHover,
      ]}
      onPress={onPress}
    >
      <Text style={{ color: 'white' }}>{title}</Text>
    </Pressable>
  );
}
